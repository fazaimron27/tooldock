<?php

/**
 * DiscordInboundProcessor
 *
 * Plugs into HookInboundController::receive() via InboundWebhookProcessorRegistry.
 *
 * Responsibilities:
 *  1. handles() — detect when the slug belongs to an active Discord BotPlatform
 *  2. verify()  — Ed25519 signature verification (401 if invalid)
 *  3. early()   — respond to Discord PING (type=1) with PONG before storing
 *  4. respond() — return {"type":5} for slash commands after storing/queuing
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Processors;

use App\Services\Registry\HookInboundProcessorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Bot\Enums\BotDriver;
use Modules\Bot\Models\BotPlatform;
use Modules\Hook\Models\HookInbound;

class DiscordInboundProcessor implements HookInboundProcessorInterface
{
    /**
     * Return true if the inbound slug is linked to an active Discord BotPlatform.
     */
    public function handles(Request $request, HookInbound $inbound): bool
    {
        return BotPlatform::where('hook_inbound_slug', $inbound->slug)
            ->where('driver', BotDriver::Discord->value)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Verify the Ed25519 signature sent by Discord.
     */
    public function verify(Request $request, HookInbound $inbound): ?JsonResponse
    {
        $platform = BotPlatform::where('hook_inbound_slug', $inbound->slug)
            ->where('driver', BotDriver::Discord->value)
            ->where('is_active', true)
            ->first();

        $publicKey = $platform?->credentials['public_key'] ?? null;

        if (! $publicKey || ! $this->verifySignature($request, $publicKey)) {
            Log::warning('DiscordInboundProcessor: signature verification failed', [
                'slug' => $inbound->slug,
                'has_pubkey' => (bool) $publicKey,
            ]);

            return response()->json(['message' => 'Invalid request signature.'], 401);
        }

        return null;
    }

    /**
     * Respond to Discord PING (type=1) immediately before storing the request.
     * Discord sends a PING to validate the Interactions Endpoint URL.
     */
    public function early(Request $request, HookInbound $inbound): ?JsonResponse
    {
        if ($request->json('type', 0) === 1) {
            return response()->json(['type' => 1]);
        }

        return null;
    }

    /**
     * After the request is stored and queued, return {"type":5} for slash commands.
     * This tells Discord to show "Bot is thinking..." while Horizon processes the job.
     */
    public function respond(Request $request, HookInbound $inbound): ?JsonResponse
    {
        if ($request->json('type', 0) === 2) {
            return response()->json(['type' => 5]);
        }

        return null;
    }

    /**
     * Verify the Discord Ed25519 request signature.
     * Requires ext-sodium (built in since PHP 7.2).
     */
    private function verifySignature(Request $request, string $publicKeyHex): bool
    {
        $signature = $request->header('X-Signature-Ed25519');
        $timestamp = $request->header('X-Signature-Timestamp');
        $body = $request->getContent();

        if (! $signature || ! $timestamp) {
            return false;
        }

        try {
            return sodium_crypto_sign_verify_detached(
                hex2bin($signature),
                $timestamp.$body,
                hex2bin($publicKeyHex),
            );
        } catch (\Exception $e) {
            Log::warning('DiscordInboundProcessor: signature verification error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
