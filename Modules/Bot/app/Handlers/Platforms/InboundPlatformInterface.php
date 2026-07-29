<?php

namespace Modules\Bot\Handlers\Platforms;

use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\Models\BotPlatform;

/**
 * Contract for platform-specific inbound message handling.
 *
 * Each platform (Telegram, Discord, …) implements this interface so that the
 * central InboundWebhookHandler stays platform-agnostic.
 */
interface InboundPlatformInterface
{
    /**
     * Parse a raw platform payload.
     *
     * @return array{0: string|null, 1: list<string>, 2: string|null, 3: string|null}
     *                                                                                [commandKey, arguments, chatUserId, platformUsername]
     */
    public function parse(array $payload): array;

    /**
     * Send a direct reply without a full BotContext.
     * Used for /start and the "not connected" prompt before a connection exists.
     */
    public function replyDirect(
        BotPlatform $platform,
        string $chatUserId,
        array $rawPayload,
        string|BotMessage $message,
    ): void;
}
