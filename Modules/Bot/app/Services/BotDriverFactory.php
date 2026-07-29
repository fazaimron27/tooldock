<?php

namespace Modules\Bot\Services;

use Modules\Bot\Drivers\DiscordDriver;
use Modules\Bot\Drivers\TelegramDriver;
use Modules\Bot\Enums\BotDriver as BotDriverEnum;
use Modules\Bot\Interfaces\BotDriverInterface;

/**
 * BotDriverFactory
 *
 * Resolves a driver string to a concrete BotDriverInterface implementation.
 * Extend the $map array to add new platform drivers.
 */
class BotDriverFactory
{
    private array $map = [
        BotDriverEnum::Telegram->value => TelegramDriver::class,
        BotDriverEnum::Discord->value => DiscordDriver::class,
    ];

    public function make(string $driver): BotDriverInterface
    {
        $class = $this->map[$driver] ?? null;

        if (! $class) {
            throw new \InvalidArgumentException("Bot driver [{$driver}] is not registered.");
        }

        return app($class);
    }

    /**
     * Register a custom driver class for a given driver key.
     */
    public function extend(string $driver, string $class): void
    {
        $this->map[$driver] = $class;
    }
}
