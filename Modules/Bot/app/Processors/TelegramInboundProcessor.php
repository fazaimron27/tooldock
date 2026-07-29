<?php

namespace Modules\Bot\Processors;

use App\Services\Registry\HookInboundProcessorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Bot\Enums\BotDriver;
use Modules\Bot\Models\BotPlatform;
use Modules\Hook\Models\HookInbound;

class TelegramInboundProcessor implements HookInboundProcessorInterface
{
    public function handles(Request $request, HookInbound $inbound): bool
    {
        return BotPlatform::where('hook_inbound_slug', $inbound->slug)
            ->where('driver', BotDriver::Telegram->value)
            ->where('is_active', true)
            ->exists();
    }

    public function verify(Request $request, HookInbound $inbound): ?JsonResponse
    {
        $platform = BotPlatform::where('hook_inbound_slug', $inbound->slug)
            ->where('driver', BotDriver::Telegram->value)
            ->where('is_active', true)
            ->first();

        $expectedToken = $platform?->credentials['webhook_secret_token'] ?? null;
        $providedToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! is_string($expectedToken)
            || ! is_string($providedToken)
            || ! hash_equals($expectedToken, $providedToken)) {
            Log::warning('TelegramInboundProcessor: secret token verification failed', [
                'slug' => $inbound->slug,
            ]);

            return response()->json(['message' => 'Invalid request secret.'], 401);
        }

        // Do not let Hook persist the plaintext secret with the request headers.
        $request->headers->remove('X-Telegram-Bot-Api-Secret-Token');

        return null;
    }

    public function early(Request $request, HookInbound $inbound): ?JsonResponse
    {
        return null;
    }

    public function respond(Request $request, HookInbound $inbound): ?JsonResponse
    {
        return null;
    }
}
