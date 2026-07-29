<?php

/**
 * HookOutboundProvider Enum
 *
 * Defines supported outbound webhook providers. Each case knows how to
 * resolve the final request URL, default headers, and default payload
 * template from encrypted provider config — keeping credentials out of the UI.
 *
 * Generic is the fallback: URL and headers are stored as-is (current behaviour).
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Enums;

use Closure;

/**
 * Enum HookOutboundProvider
 */
enum HookOutboundProvider: string
{
    case Generic = 'generic';
    case Telegram = 'telegram';
    case Discord = 'discord';
    case Slack = 'slack';

    /**
     * Human-readable label shown in the UI dropdown.
     */
    public function label(): string
    {
        return match ($this) {
            self::Generic => 'Generic / Custom',
            self::Telegram => 'Telegram',
            self::Discord => 'Discord',
            self::Slack => 'Slack',
        };
    }

    /**
     * Config field definitions rendered by the frontend.
     *
     * Each entry: [ key, label, type, placeholder ]
     *
     * @return list<array{key: string, label: string, type: string, placeholder: string}>
     */
    public function configFields(): array
    {
        return match ($this) {
            self::Generic => [],  // uses target_url + headers as before

            self::Telegram => [
                ['key' => 'token',   'label' => 'Bot Token',  'type' => 'password', 'placeholder' => 'Paste your bot token'],
                ['key' => 'chat_id', 'label' => 'Chat ID',    'type' => 'text',     'placeholder' => 'e.g. 123456789'],
            ],

            self::Discord => [
                ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'password', 'placeholder' => 'https://discord.com/api/webhooks/...'],
            ],

            self::Slack => [
                ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'password', 'placeholder' => 'https://hooks.slack.com/services/...'],
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function configKeys(): array
    {
        return array_column($this->configFields(), 'key');
    }

    /**
     * Build the final request URL from decrypted provider config.
     *
     * @param  array<string, mixed>  $config
     */
    public function resolveUrl(array $config): ?string
    {
        return match ($this) {
            self::Generic => null, // caller uses outbound->target_url
            self::Telegram => isset($config['token'])
                ? 'https://api.telegram.org/bot'.$config['token'].'/sendMessage'
                : null,
            self::Discord,
            self::Slack => $config['webhook_url'] ?? null,
        };
    }

    /**
     * Determine whether a resolved managed-provider URL matches its exact endpoint.
     */
    public function isValidUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);

        if (! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user'], $parts['pass'], $parts['port'], $parts['query'], $parts['fragment'])) {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        return match ($this) {
            self::Telegram => $host === 'api.telegram.org'
                && preg_match('#\A/bot[0-9]+:[A-Za-z0-9_-]+/sendMessage\z#D', $path) === 1,
            self::Discord => $host === 'discord.com'
                && preg_match('#\A/api/webhooks/[0-9]+/[A-Za-z0-9._-]+\z#D', $path) === 1,
            self::Slack => $host === 'hooks.slack.com'
                && preg_match('#\A/services/T[A-Z0-9]+/B[A-Z0-9]+/[A-Za-z0-9]+\z#D', $path) === 1,
            self::Generic => false,
        };
    }

    /**
     * Provider-specific validation rules for managed webhook URLs.
     *
     * @return array<string, list<mixed>>
     */
    public function providerConfigValidationRules(bool $required = true): array
    {
        if (! in_array($this, [self::Discord, self::Slack], true)) {
            return [];
        }

        return [
            'provider_config.webhook_url' => [
                $required ? 'required' : 'sometimes',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! $this->isValidUrl($value)) {
                        $fail("The {$attribute} must be a valid {$this->label()} HTTPS webhook URL.");
                    }
                },
            ],
        ];
    }

    /**
     * Default request headers for this provider.
     *
     * @return array<string, string>
     */
    public function defaultHeaders(): array
    {
        return match ($this) {
            self::Generic => [],
            self::Telegram,
            self::Discord,
            self::Slack => ['Content-Type' => 'application/json'],
        };
    }

    /**
     * Default payload_template for this provider.
     *
     * @return array<string, mixed>
     */
    public function defaultPayload(): array
    {
        return match ($this) {
            self::Generic => ['event' => 'test', 'data' => []],
            self::Telegram => [
                'chat_id' => '{{chat_id}}',
                'text' => 'Hello from Tool Dock!',
            ],
            self::Discord => [
                'content' => 'Hello from Tool Dock!',
            ],
            self::Slack => [
                'text' => 'Hello from Tool Dock!',
            ],
        };
    }

    /**
     * Return a safe display URL — token is masked for Telegram.
     */
    public function maskedUrl(array $config): string
    {
        return match ($this) {
            self::Generic => '',
            self::Telegram => isset($config['token'])
                ? 'https://api.telegram.org/bot***'.substr($config['token'], -6).'/sendMessage'
                : 'https://api.telegram.org/bot***/sendMessage',
            self::Discord,
            self::Slack => isset($config['webhook_url'])
                ? (function (string $url): string {
                    $p = parse_url($url);

                    return ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '?').'/***';
                })($config['webhook_url'])
                : '***',
        };
    }

    /**
     * All providers as [ value => label ] for frontend select options.
     *
     * @return list<array{value: string, label: string, configFields: list<mixed>}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $p) => [
                'value' => $p->value,
                'label' => $p->label(),
                'configFields' => $p->configFields(),
                'defaultPayload' => $p->defaultPayload(),
            ],
            self::cases(),
        );
    }
}
