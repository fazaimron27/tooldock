<?php

namespace Modules\Bot\Handlers\Platforms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\BotUI\Components\BotButton;
use Modules\Bot\Models\BotPlatform;

/**
 * DiscordInboundPlatform
 *
 * Parses inbound Discord Interactions webhook payloads.
 * Routes explicitly by interaction type — future-proof for buttons, modals, etc.
 *
 * Discord Interaction Types (from Discord API docs):
 *
 * @see https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-interaction-type
 */
class DiscordInboundPlatform implements InboundPlatformInterface
{
    // Discord Interaction Type constants
    private const TYPE_PING = 1;

    private const TYPE_APPLICATION_COMMAND = 2; // slash commands

    private const TYPE_MESSAGE_COMPONENT = 3; // buttons, select menus

    private const TYPE_AUTOCOMPLETE = 4; // command autocomplete

    private const TYPE_MODAL_SUBMIT = 5; // modal form submissions

    /**
     * Parse the raw Discord webhook payload into a normalised tuple.
     *
     * Returns: [commandKey|null, args[], userId|null, username|null]
     */
    public function parse(array $payload): array
    {
        return match ((int) ($payload['type'] ?? 0)) {
            self::TYPE_APPLICATION_COMMAND => $this->parseSlashCommand($payload),
            self::TYPE_MESSAGE_COMPONENT => $this->parseMessageComponent($payload),
            self::TYPE_AUTOCOMPLETE => $this->parseAutocomplete($payload),
            self::TYPE_MODAL_SUBMIT => $this->parseModalSubmit($payload),
            default => [null, [], null, null],
        };
    }

    /**
     * Send a followup message to a deferred Discord interaction.
     *
     * Discord slash commands must receive a deferred response (type 5) immediately,
     * then this method posts the actual content to the followup endpoint.
     * The interaction token is always available in the raw payload['token'].
     */
    public function replyDirect(
        BotPlatform $platform,
        string $chatUserId,
        array $rawPayload,
        string|BotMessage $message,
    ): void {
        $applicationId = $platform->credentials['application_id'] ?? null;
        $token = $rawPayload['token'] ?? null;

        if (! $applicationId || ! $token) {
            Log::warning('DiscordInboundPlatform::replyDirect: missing application_id or interaction_token.');

            return;
        }

        $text = $message instanceof BotMessage ? $message->text : $message;
        $components = $message instanceof BotMessage ? $this->renderComponents($message) : [];
        $endpoint = "https://discord.com/api/v10/webhooks/{$applicationId}/{$token}";

        $body = array_filter(['content' => $text, 'components' => $components]);

        try {
            Http::post($endpoint, $body);
        } catch (\Exception $e) {
            Log::error('DiscordInboundPlatform::replyDirect failed', ['error' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers — one per Discord interaction type
    // -------------------------------------------------------------------------

    /**
     * Parse a TYPE_APPLICATION_COMMAND (slash command) interaction.
     */
    private function parseSlashCommand(array $payload): array
    {
        $data = $payload['data'] ?? [];
        $member = $payload['member'] ?? [];
        $user = $member['user'] ?? ($payload['user'] ?? []);
        $command = strtolower($data['name'] ?? '');
        $userId = (string) ($user['id'] ?? '');
        $token = $payload['token'] ?? null;

        // Prefer: server nickname → Discord global display name → unique username
        $username = $member['nick']
            ?? $user['username']
            ?? $user['global_name']
            ?? null;

        // Extract options into a key => value args map
        $args = $this->extractOptions($data['options'] ?? []);

        return [$command ?: null, $args, $userId ?: null, $username, 'interaction_token' => $token];
    }

    /**
     * Parse a TYPE_MESSAGE_COMPONENT (button / select menu) interaction.
     *
     * Stub — returns null command so InboundWebhookHandler ignores it for now.
     * Wire up when button interactions are implemented.
     */
    private function parseMessageComponent(array $payload): array
    {
        // $payload['data']['custom_id'] = button identifier
        // $payload['data']['values']    = selected values (select menu)
        return [null, [], null, null];
    }

    /**
     * Parse a TYPE_AUTOCOMPLETE interaction.
     *
     * Stub — autocomplete responses require a different response format (type 8).
     * Wire up when autocomplete features are implemented.
     */
    private function parseAutocomplete(array $payload): array
    {
        return [null, [], null, null];
    }

    /**
     * Parse a TYPE_MODAL_SUBMIT interaction.
     *
     * Stub — modal submissions contain components with user-entered text fields.
     * Wire up when modal flows are implemented.
     */
    private function parseModalSubmit(array $payload): array
    {
        // $payload['data']['custom_id']    = modal identifier
        // $payload['data']['components']   = array of action rows with text inputs
        return [null, [], null, null];
    }

    /**
     * Flatten Discord command options into a simple key => value array.
     */
    private function extractOptions(array $options): array
    {
        $result = [];
        foreach ($options as $option) {
            $result[$option['name']] = $option['value'] ?? null;
        }

        return $result;
    }

    /**
     * Build Discord components (action row with link buttons) from BotMessage components.
     *
     * Discord link button: type=2, style=5 (LINK), requires a URL.
     *
     * @see https://discord.com/developers/docs/interactions/message-components#button-object
     */
    private function renderComponents(BotMessage $message): array
    {
        $buttons = [];

        foreach ($message->components as $component) {
            if ($component instanceof BotButton && $component->url) {
                $buttons[] = [
                    'type' => 2,    // BUTTON
                    'style' => 5,    // LINK
                    'label' => $component->label,
                    'url' => $component->url,
                ];
            }
        }

        if (empty($buttons)) {
            return [];
        }

        return [['type' => 1, 'components' => $buttons]]; // ACTION_ROW
    }
}
