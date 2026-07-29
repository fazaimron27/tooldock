<?php

/**
 * InboundWebhookHandler
 *
 * Listens to 'hook.webhook.received'. If the inbound slug belongs to an active
 * BotPlatform, resolves the correct platform handler, parses the command, and
 * dispatches it as the linked user.
 *
 * Platform-specific logic lives in dedicated handlers under Handlers/Platforms/:
 *   - TelegramInboundPlatform  — payload parsing + Telegram-safe replies
 *   - DiscordInboundPlatform   — payload parsing + Discord followup replies
 *
 * Account linking flow:
 *  - /start        → generate a signed connect URL, reply (no connection required)
 *  - Other commands → look up BotConnection; if missing, prompt user to /start first
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Handlers;

use App\Services\Registry\BotCommandRegistryInterface;
use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\BotUI\Components\BotButton;
use Modules\Bot\Enums\BotMessageDirection;
use Modules\Bot\Enums\BotMessageStatus;
use Modules\Bot\Handlers\Platforms\DiscordInboundPlatform;
use Modules\Bot\Handlers\Platforms\InboundPlatformInterface;
use Modules\Bot\Handlers\Platforms\TelegramInboundPlatform;
use Modules\Bot\Models\BotConnection;
use Modules\Bot\Models\BotMessage as BotMessageModel;
use Modules\Bot\Models\BotPlatform;
use Modules\Bot\Services\BotContext;
use Modules\Bot\Services\BotManager;
use Modules\Hook\Models\HookInboundRequest;

class InboundWebhookHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly BotCommandRegistryInterface $registry,
        private readonly BotManager $manager,
        private readonly TelegramInboundPlatform $telegram,
        private readonly DiscordInboundPlatform $discord,
    ) {}

    public function getEvents(): array
    {
        return ['hook.webhook.received'];
    }

    public function getModule(): string
    {
        return 'Bot';
    }

    public function getName(): string
    {
        return 'inbound_webhook';
    }

    public function supports(string $event, mixed $data): bool
    {
        return $event === 'hook.webhook.received'
            && is_array($data)
            && ($data['request'] ?? null) instanceof HookInboundRequest;
    }

    // ── Main entry ────────────────────────────────────────────────────────────

    public function handle(mixed $data): ?array
    {
        /** @var HookInboundRequest $inboundRequest */
        $inboundRequest = $data['request'];

        $inbound = $inboundRequest->inbound ?? $inboundRequest->load('inbound')->inbound;
        $platform = BotPlatform::where('hook_inbound_slug', $inbound?->slug)
            ->where('is_active', true)
            ->first();

        if (! $platform) {
            return null;
        }

        $rawPayload = $inboundRequest->payload ?? [];
        $platformHandler = $this->resolvePlatformHandler($platform->driver->value);

        if (! $platformHandler) {
            return null;
        }

        [$commandKey, $arguments, $chatUserId, $platformUsername] = $platformHandler->parse($rawPayload);

        $messageLog = BotMessageModel::create([
            'user_id' => $platform->user_id,
            'bot_platform_id' => $platform->id,
            'direction' => BotMessageDirection::Inbound,
            'command_key' => $commandKey,
            'raw_payload' => $rawPayload,
            'status' => BotMessageStatus::Delivered,
        ]);

        if (! $commandKey || ! $chatUserId) {
            return null;
        }

        // /start is always handled before the connection check
        if ($commandKey === 'start') {
            $this->handleStart($platform, $chatUserId, $platformUsername ?? $chatUserId, $rawPayload, $platformHandler);

            return null;
        }

        // All other commands require an established connection
        $connection = BotConnection::where('bot_platform_id', $platform->id)
            ->where('platform_user_id', $chatUserId)
            ->first();

        if (! $connection) {
            $platformHandler->replyDirect(
                $platform,
                $chatUserId,
                $rawPayload,
                "You're not connected yet. Type /start to link your Tool Dock account."
            );

            return null;
        }

        $command = $this->registry->resolve($commandKey);

        if (! $command) {
            return null;
        }

        try {
            $interactionToken = $platform->driver->value === 'discord'
                ? ($rawPayload['token'] ?? null)
                : null;

            $context = new BotContext(
                platform: $platform,
                chatUserId: (string) $chatUserId,
                arguments: $arguments,
                manager: $this->manager,
                ownerUserId: $connection->user_id,
                interactionToken: $interactionToken,
            );

            ($command['handler'])($context);
        } catch (\Exception $e) {
            $messageLog->update([
                'status' => BotMessageStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('InboundWebhookHandler: command dispatch failed', [
                'platform' => $platform->id,
                'command' => $commandKey,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    // ── /start flow ───────────────────────────────────────────────────────────

    /**
     * Handle the /start command — generate a signed connect URL and reply.
     *
     * The URL is handed to the platform handler's replyDirect() so each
     * platform can render it appropriately (plain text for Telegram,
     * markdown link for Discord).
     */
    private function handleStart(
        BotPlatform $platform,
        string $chatUserId,
        string $platformUsername,
        array $rawPayload,
        InboundPlatformInterface $platformHandler,
    ): void {
        $existing = BotConnection::where('bot_platform_id', $platform->id)
            ->where('platform_user_id', $chatUserId)
            ->first();

        if ($existing) {
            $platformHandler->replyDirect(
                $platform,
                $chatUserId,
                $rawPayload,
                'Your account is already connected. You can use commands like /habits.'
            );

            return;
        }

        $relativeConnectUrl = URL::temporarySignedRoute('bot.connect', now()->addMinutes(10), [
            'bot_platform_id' => $platform->id,
            'platform_user_id' => $chatUserId,
            'platform_username' => $platformUsername,
        ], absolute: false);
        $connectUrl = rtrim(config('bot.connect_url'), '/').$relativeConnectUrl;

        $platformHandler->replyDirect(
            $platform,
            $chatUserId,
            $rawPayload,
            BotMessage::make(
                'To use this bot, link your Tool Dock account:',
                [new BotButton('Connect Account →', '', 'primary', $connectUrl)]
            )
        );
    }

    // ── Platform resolution ───────────────────────────────────────────────────

    private function resolvePlatformHandler(string $driver): ?InboundPlatformInterface
    {
        return match ($driver) {
            'telegram' => $this->telegram,
            'discord' => $this->discord,
            default => null,
        };
    }
}
