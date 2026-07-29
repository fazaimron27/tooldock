<?php

namespace Modules\Bot\Drivers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\BotUI\Components\BotButton;
use Modules\Bot\BotUI\Components\BotCard;
use Modules\Bot\BotUI\Components\BotText;
use Modules\Bot\Models\BotPlatform;

/**
 * TelegramDriver
 *
 * Sends messages via the Telegram Bot API.
 * BotCard        -> formatted Markdown text block
 * BotButton(s)   -> InlineKeyboard row
 * BotText(bold)  -> *bold* Markdown
 */
class TelegramDriver extends BaseDriver
{
    private function apiUrl(string $token, string $method): string
    {
        return "https://api.telegram.org/bot{$token}/{$method}";
    }

    public function send(BotMessage $message, BotPlatform $platform): bool
    {
        $creds = $platform->credentials ?? [];
        $token = $creds['bot_token'] ?? null;
        $chatId = $creds['chat_id'] ?? null;

        if (! $token || ! $chatId) {
            return false;
        }

        $text = $this->renderText($message);
        $replyMarkup = $this->renderComponents($message->components);

        $body = array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $replyMarkup ? json_encode($replyMarkup) : null,
        ]);

        /** @var Response $response */
        $response = $this->http()->post($this->apiUrl($token, 'sendMessage'), $body);

        return $response->successful() && ($response->json('ok') === true);
    }

    public function testConnection(BotPlatform $platform): array
    {
        $token = $platform->credentials['bot_token'] ?? null;

        if (! $token) {
            return ['ok' => false, 'message' => 'No bot token configured.'];
        }

        try {
            /** @var Response $response */
            $response = $this->http()->get($this->apiUrl($token, 'getMe'));

            if ($response->successful() && $response->json('ok') === true) {
                $username = $response->json('result.username', 'unknown');

                return ['ok' => true, 'message' => "Connected as @{$username}"];
            }

            return ['ok' => false, 'message' => $response->json('description', 'Invalid token or network error.')];
        } catch (\Exception $e) {
            Log::error('TelegramDriver::testConnection failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Connection error: '.$e->getMessage()];
        }
    }

    public function registerWebhook(BotPlatform $platform, string $webhookUrl): void
    {
        $credentials = $platform->credentials ?? [];
        $token = $credentials['bot_token'] ?? null;

        if (! $token) {
            return;
        }

        if (empty($credentials['webhook_secret_token'])) {
            $credentials['webhook_secret_token'] = Str::random(64);
            $platform->updateQuietly(['credentials' => $credentials]);
        }

        try {
            /** @var Response $response */
            $response = $this->http()->post($this->apiUrl($token, 'setWebhook'), [
                'url' => $webhookUrl,
                'secret_token' => $credentials['webhook_secret_token'],
            ]);

            if (! $response->json('ok')) {
                Log::warning('TelegramDriver::registerWebhook failed', [
                    'platform' => $platform->id,
                    'description' => $response->json('description'),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('TelegramDriver::registerWebhook exception', [
                'platform' => $platform->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function renderComponents(array $components): array
    {
        $buttons = array_filter($components, fn ($c) => $c instanceof BotButton);

        if (empty($buttons)) {
            return [];
        }

        $row = array_map(
            fn (BotButton $b) => $b->url
                ? ['text' => $b->label, 'url' => $b->url]
                : ['text' => $b->label, 'callback_data' => $b->action],
            array_values($buttons)
        );

        return ['inline_keyboard' => [$row]];
    }

    private function renderText(BotMessage $message): string
    {
        $parts = [$message->text];

        foreach ($message->components as $component) {
            if ($component instanceof BotCard) {
                $parts[] = "\n*{$component->title}*";
                foreach ($component->fields as $key => $value) {
                    $parts[] = "{$key}: `{$value}`";
                }
            } elseif ($component instanceof BotText) {
                $parts[] = match ($component->style) {
                    'bold' => "*{$component->content}*",
                    'italic' => "_{$component->content}_",
                    'code' => "`{$component->content}`",
                    default => $component->content,
                };
            }
        }

        return implode("\n", $parts);
    }
}
