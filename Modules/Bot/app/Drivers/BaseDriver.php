<?php

namespace Modules\Bot\Drivers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Bot\BotUI\BotMessage;
use Modules\Bot\Interfaces\BotDriverInterface;
use Modules\Bot\Models\BotPlatform;

/**
 * BaseDriver
 *
 * Provides shared HTTP helper and defines the abstract contract
 * that concrete drivers must implement.
 */
abstract class BaseDriver implements BotDriverInterface
{
    abstract public function send(BotMessage $message, BotPlatform $platform): bool;

    abstract public function testConnection(BotPlatform $platform): array;

    abstract public function renderComponents(array $components): array;

    /**
     * Build a pre-configured HTTP client with a timeout and accept-json header.
     */
    protected function http(array $headers = []): PendingRequest
    {
        return Http::timeout(15)
            ->withoutRedirecting()
            ->acceptJson()
            ->withHeaders($headers);
    }
}
