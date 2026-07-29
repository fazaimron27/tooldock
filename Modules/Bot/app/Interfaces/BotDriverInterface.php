<?php

namespace Modules\Bot\Interfaces;

use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\Models\BotPlatform;

/**
 * Interface BotDriverInterface
 *
 * Contract every platform driver must fulfill.
 */
interface BotDriverInterface
{
    /**
     * Send a BotMessage to the given platform.
     */
    public function send(BotMessage $message, BotPlatform $platform): bool;

    /**
     * Test that the stored credentials actually connect to the API.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(BotPlatform $platform): array;

    /**
     * Register (or re-register) the inbound webhook URL with the platform's API.
     * Drivers that don't support server-side webhook registration (e.g. Discord)
     * should implement this as a no-op.
     */
    public function registerWebhook(BotPlatform $platform, string $webhookUrl): void;

    /**
     * Render an array of BotComponents into platform-native JSON structure.
     */
    public function renderComponents(array $components): array;
}
