<?php

namespace Modules\Bot\Drivers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\BotUI\Components\BotButton;
use Modules\Bot\BotUI\Components\BotCard;
use Modules\Bot\BotUI\Components\BotText;
use Modules\Bot\Models\BotPlatform;

/**
 * DiscordDriver
 *
 * Sends messages via Discord Webhook API.
 * BotCard        -> Rich Embed
 * BotButton(s)   -> Embed footer note (Discord webhooks cannot create buttons)
 * BotText        -> Embed description text
 */
class DiscordDriver extends BaseDriver
{
    public const WEBHOOK_URL_PATTERN = '~\Ahttps://discord\.com/api/webhooks/[0-9]+/[A-Za-z0-9._-]+\z~D';

    private const FOLLOWUP_URL_PATTERN = '~\Ahttps://discord\.com/api/v10/webhooks/[0-9]+/[A-Za-z0-9._-]+\z~D';

    public function send(BotMessage $message, BotPlatform $platform): bool
    {
        $webhookUrl = $platform->credentials['webhook_url'] ?? null;

        if (! $this->isValidWebhookUrl($webhookUrl)) {
            return false;
        }

        $payload = $this->buildPayload($message);

        try {
            /** @var Response $response */
            $response = $this->http()->post($webhookUrl, $payload);

            return $response->status() === 204 || $response->successful();
        } catch (\Exception $e) {
            Log::error('DiscordDriver::send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function testConnection(BotPlatform $platform): array
    {
        $webhookUrl = $platform->credentials['webhook_url'] ?? null;

        if (! $this->isValidWebhookUrl($webhookUrl)) {
            return ['ok' => false, 'message' => 'Invalid Discord webhook URL.'];
        }

        try {
            /** @var Response $response */
            $response = $this->http()->get($webhookUrl);

            if ($response->successful()) {
                $name = $response->json('name', 'Unknown');

                return ['ok' => true, 'message' => "Connected to webhook: {$name}"];
            }

            return ['ok' => false, 'message' => 'Invalid webhook URL or network error.'];
        } catch (\Exception $e) {
            Log::error('DiscordDriver::testConnection failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Connection error: '.$e->getMessage()];
        }
    }

    public function registerWebhook(BotPlatform $platform, string $webhookUrl): void
    {
        // Discord uses an Interactions Endpoint URL set in the Discord Developer Portal.
        // Server-side webhook registration is not supported — no-op.
    }

    /**
     * Send a followup message to a deferred Discord interaction.
     *
     * Must be called after the initial {"type":5} deferred response.
     * Uses the Interaction followup webhook:
     *   POST https://discord.com/api/v10/webhooks/{application_id}/{interaction_token}
     */
    public function sendFollowup(?string $applicationId, string $token, string $text): bool
    {
        if (! $applicationId) {
            Log::warning('DiscordDriver::sendFollowup: no application_id configured.');

            return false;
        }

        $url = "https://discord.com/api/v10/webhooks/{$applicationId}/{$token}";

        if (preg_match(self::FOLLOWUP_URL_PATTERN, $url) !== 1) {
            Log::warning('DiscordDriver::sendFollowup: invalid application ID or token.');

            return false;
        }

        try {
            /** @var Response $response */
            $response = $this->http()->post($url, ['content' => $text]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('DiscordDriver::sendFollowup failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function renderComponents(array $components): array
    {
        $embeds = [];

        foreach ($components as $component) {
            if ($component instanceof BotCard) {
                $fields = array_map(fn ($value, $name) => [
                    'name' => $name,
                    'value' => $value,
                    'inline' => true,
                ], $component->fields, array_keys($component->fields));

                $embeds[] = [
                    'title' => $component->title,
                    'color' => hexdec(ltrim($component->color, '#')),
                    'fields' => $fields,
                ];
            }
        }

        return $embeds;
    }

    private function buildPayload(BotMessage $message): array
    {
        $embeds = $this->renderComponents($message->components);

        $descriptionParts = [$message->text];
        foreach ($message->components as $component) {
            if ($component instanceof BotText) {
                $descriptionParts[] = match ($component->style) {
                    'bold' => "**{$component->content}**",
                    'italic' => "_{$component->content}_",
                    'code' => "`{$component->content}`",
                    default => $component->content,
                };
            } elseif ($component instanceof BotButton) {
                $descriptionParts[] = "▸ [{$component->label}]({$component->action})";
            }
        }

        if (! empty($descriptionParts)) {
            if (empty($embeds)) {
                $embeds[] = ['description' => implode("\n", $descriptionParts), 'color' => 0x5865F2];
            } else {
                $embeds[0]['description'] = implode("\n", $descriptionParts);
            }
        }

        return ['embeds' => $embeds];
    }

    private function isValidWebhookUrl(mixed $webhookUrl): bool
    {
        return is_string($webhookUrl)
            && preg_match(self::WEBHOOK_URL_PATTERN, $webhookUrl) === 1;
    }
}
