<?php

namespace Modules\Bot\Enums;

enum BotDriver: string
{
    case Telegram = 'telegram';
    case Discord = 'discord';

    public function label(): string
    {
        return match ($this) {
            self::Telegram => 'Telegram',
            self::Discord => 'Discord',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Telegram => 'send',
            self::Discord => 'message-circle',
        };
    }

    /**
     * @return list<array{value: string, label: string, icon: string, fields: list<array{key: string, label: string, type: string, placeholder: string}>}>
     */
    public static function toOptions(): array
    {
        return array_map(fn (self $d) => [
            'value' => $d->value,
            'label' => $d->label(),
            'icon' => $d->icon(),
            'fields' => $d->credentialFields(),
        ], self::cases());
    }

    /**
     * @return list<array{key: string, label: string, type: string, placeholder: string}>
     */
    public function credentialFields(): array
    {
        return match ($this) {
            self::Telegram => [
                ['key' => 'bot_token', 'label' => 'Bot Token', 'type' => 'password', 'placeholder' => '123456:ABC-DEF...'],
                ['key' => 'chat_id',   'label' => 'Chat ID',   'type' => 'text',     'placeholder' => '-100123456789'],
            ],
            self::Discord => [
                ['key' => 'webhook_url',    'label' => 'Webhook URL',   'type' => 'password', 'placeholder' => 'https://discord.com/api/webhooks/...'],
                ['key' => 'application_id', 'label' => 'Application ID', 'type' => 'text',     'placeholder' => '1234567890123456789'],
                ['key' => 'public_key',     'label' => 'Public Key',    'type' => 'password', 'placeholder' => 'a1b2c3d4... (from Developer Portal)'],
                ['key' => 'bot_token',      'label' => 'Bot Token',     'type' => 'password', 'placeholder' => 'MTIzND... (Bot → Token)'],
            ],
        };
    }
}
