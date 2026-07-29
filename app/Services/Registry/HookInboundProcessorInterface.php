<?php

/**
 * HookInboundProcessorInterface
 *
 * Contract for modules that need to intercept Hook inbound requests
 * before they are stored and queued.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Hook\Models\HookInbound;

interface HookInboundProcessorInterface
{
    /**
     * Return true if this processor applies to the given inbound endpoint.
     */
    public function handles(Request $request, HookInbound $inbound): bool;

    /**
     * Verify the authenticity of the request.
     * Return a JsonResponse to abort (e.g. 401). Return null to continue.
     * Called only when handles() returns true.
     */
    public function verify(Request $request, HookInbound $inbound): ?JsonResponse;

    /**
     * Short-circuit before the request is stored and queued.
     * Return a JsonResponse to respond immediately (e.g. PING -> PONG, skips storing).
     * Return null to let the normal Hook flow proceed.
     * Called only when handles() returns true AND verify() returned null.
     */
    public function early(Request $request, HookInbound $inbound): ?JsonResponse;

    /**
     * Override the success response AFTER the request has been stored and queued.
     * Return a JsonResponse to replace the default {"message":"Webhook received."}.
     * Return null to keep the default Hook response.
     *
     * Use case: Discord slash commands (type=2) must receive {"type":5} so Discord
     * shows "Bot is thinking..." while Horizon processes the job asynchronously.
     */
    public function respond(Request $request, HookInbound $inbound): ?JsonResponse;
}
