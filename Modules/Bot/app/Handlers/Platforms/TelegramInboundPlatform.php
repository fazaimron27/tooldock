<?php

namespace Modules\Bot\Handlers\Platforms;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\BotUI\Components\BotButton;
use Modules\Bot\Models\BotPlatform;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\InlineQuery;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

/**
 * TelegramInboundPlatform
 *
 * Parses inbound Telegram webhook payloads using the irazasyed/telegram-bot-sdk
 * typed Update object. Routes by update type — future-proof for callbacks, photos, etc.
 */
class TelegramInboundPlatform implements InboundPlatformInterface
{
    /**
     * Parse the raw Telegram webhook payload into a normalised tuple.
     *
     * Returns: [commandKey|null, args[], chatUserId|null, username|null]
     */
    public function parse(array $payload): array
    {
        $update = new Update($payload);

        return match ($update->objectType()) {
            'message' => $this->parseMessage($update->message),
            'callback_query' => $this->parseCallbackQuery($update->callbackQuery),
            'inline_query' => $this->parseInlineQuery($update->inlineQuery),
            default => [null, [], null, null],
        };
    }

    /**
     * Send a plain-text reply directly to a Telegram chat via the Bot API.
     *
     * When a BotMessage is passed, BotButton URLs are appended as plain-text
     * lines so the connect link is visible and tappable in Telegram.
     * Underscores in URLs are escaped for Telegram Markdown v1.
     */
    public function replyDirect(
        BotPlatform $platform,
        string $chatUserId,
        array $rawPayload,
        string|BotMessage $message,
    ): void {
        $token = $platform->credentials['bot_token'] ?? null;
        $chatId = $rawPayload['message']['chat']['id']
            ?? $rawPayload['callback_query']['message']['chat']['id']
            ?? $chatUserId;

        if (! $token || ! $chatId) {
            Log::warning('TelegramInboundPlatform::replyDirect: missing token or chat_id.');

            return;
        }

        $text = $this->renderMessage($message);
        $safeText = $this->makeMessageSafe($text);

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $safeText,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::error('TelegramInboundPlatform::replyDirect failed', ['error' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers — one per Telegram update type
    // -------------------------------------------------------------------------

    /**
     * Parse a `message` update (text, photo, document, sticker, etc.)
     */
    private function parseMessage(Message $msg): array
    {
        $text = $msg->get('text', '');
        $from = $msg->get('from', collect());
        $chat = $msg->get('chat', collect());
        $userId = $from instanceof Collection ? $from->get('id') : ($from['id'] ?? null);
        $username = $from instanceof Collection ? $from->get('username') : ($from['username'] ?? null);
        $chatId = $chat instanceof Collection ? $chat->get('id') : ($chat['id'] ?? null);

        [$command, $args] = $this->parseTextCommand($text);

        return [$command, $args, (string) $userId, $username, 'chat_id' => $chatId];
    }

    /**
     * Parse a `callback_query` update (inline button press).
     *
     * The `data` field contains whatever string was set as callback_data on the button.
     */
    private function parseCallbackQuery(CallbackQuery $cbq): array
    {
        $from = $cbq->get('from', collect());
        $userId = $from instanceof Collection ? $from->get('id') : ($from['id'] ?? null);
        $username = $from instanceof Collection ? $from->get('username') : ($from['username'] ?? null);
        $data = $cbq->get('data', '');
        $message = $cbq->get('message', collect());
        $chatId = $message instanceof Collection ? $message->get('chat', collect())->get('id') : null;

        // Treat callback data as the command key (e.g. "confirm_habit:123")
        [$command, $args] = $this->parseTextCommand($data);

        return [$command, $args, (string) $userId, $username, 'chat_id' => $chatId];
    }

    /**
     * Parse an `inline_query` update — stub, ready for future use.
     */
    private function parseInlineQuery(InlineQuery $iq): array
    {
        return [null, [], null, null];
    }

    /**
     * Extract a command key and args from a text string.
     *
     * Supports `/command arg1 arg2` and plain `action:value` formats.
     */
    private function parseTextCommand(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [null, []];
        }

        if (str_starts_with($text, '/')) {
            $parts = explode(' ', ltrim($text, '/'));
            $command = strtolower(strtok($parts[0], '@')); // strip @BotName suffix
            $args = array_slice($parts, 1);

            return [$command, $args];
        }

        // Callback data like "confirm_habit:123" — command = "confirm_habit", args = ["123"]
        if (str_contains($text, ':')) {
            [$command, $rest] = explode(':', $text, 2);

            return [strtolower($command), [$rest]];
        }

        return [strtolower($text), []];
    }

    /**
     * Render a string or BotMessage into a single plain-text string.
     *
     * BotButton URLs are appended as plain-text lines so they appear as
     * tappable links in Telegram (Markdown v1 doesn't support inline links well).
     */
    private function renderMessage(string|BotMessage $message): string
    {
        if (is_string($message)) {
            return $message;
        }

        $parts = [$message->text];

        foreach ($message->components as $component) {
            if ($component instanceof BotButton && $component->url) {
                $parts[] = $component->url;
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Escape underscores in URLs so Telegram Markdown v1 doesn't misinterpret them.
     */
    private function makeMessageSafe(string $text): string
    {
        return preg_replace_callback(
            '/(https?:\/\/\S+)/',
            fn ($m) => str_replace('_', '\\_', $m[0]),
            $text
        );
    }
}
