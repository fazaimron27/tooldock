<?php

namespace Modules\Bot\Services;

use Illuminate\Support\Facades\Log;
use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\Enums\BotMessageDirection;
use Modules\Bot\Enums\BotMessageStatus;
use Modules\Bot\Interfaces\BotDriverInterface;
use Modules\Bot\Models\BotMessage as BotMessageModel;
use Modules\Bot\Models\BotPlatform;

/**
 * BotManager
 *
 * Central orchestrator. Exposes a fluent API:
 *   Bot::to($platform)->send($message);
 *   Bot::test($platform);
 *
 * All sends are logged to bot_messages.
 */
class BotManager
{
    private ?BotPlatform $currentPlatform = null;

    public function __construct(private readonly BotDriverFactory $factory) {}

    /**
     * Set the target platform for the next operation.
     */
    public function to(BotPlatform $platform): static
    {
        $this->currentPlatform = $platform;

        return $this;
    }

    /**
     * Send a BotMessage through the configured platform driver.
     * Automatically logs the attempt to bot_messages.
     */
    public function send(BotMessage $message, ?string $commandKey = null): bool
    {
        $platform = $this->currentPlatform;

        if (! $platform) {
            throw new \RuntimeException('No platform selected. Call Bot::to($platform) first.');
        }

        $log = BotMessageModel::create([
            'user_id' => $platform->user_id,
            'bot_platform_id' => $platform->id,
            'direction' => BotMessageDirection::Outbound,
            'command_key' => $commandKey,
            'raw_payload' => ['text' => $message->text],
            'status' => BotMessageStatus::Pending,
        ]);

        try {
            $driver = $this->factory->make($platform->driver->value);
            $success = $driver->send($message, $platform);

            $log->update([
                'status' => $success
                    ? BotMessageStatus::Delivered
                    : BotMessageStatus::Failed,
            ]);

            return $success;
        } catch (\Exception $e) {
            Log::error('BotManager::send failed', ['platform' => $platform->id, 'error' => $e->getMessage()]);
            $log->update([
                'status' => BotMessageStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Return the raw driver instance for a given driver key.
     * Use only for driver-specific operations not covered by the interface
     * (e.g. Discord's sendFollowup).
     */
    public function driver(string $driverKey): BotDriverInterface
    {
        return $this->factory->make($driverKey);
    }

    /**
     * Test the API connection for a platform.
     *
     * @return array{ok: bool, message: string}
     */
    public function test(BotPlatform $platform): array
    {
        try {
            $driver = $this->factory->make($platform->driver->value);
            $result = $driver->testConnection($platform);

            $platform->update(['tested_at' => now()]);

            return $result;
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
