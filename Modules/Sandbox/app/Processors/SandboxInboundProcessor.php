<?php

namespace Modules\Sandbox\Processors;

use App\Services\Registry\HookInboundProcessorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Hook\Models\HookInbound;

class SandboxInboundProcessor implements HookInboundProcessorInterface
{
    public function handles(Request $request, HookInbound $inbound): bool
    {
        $probeHeader = (string) config('sandbox.probe_header', 'X-Sandbox-Probe');

        return $request->headers->has($probeHeader);
    }

    public function verify(Request $request, HookInbound $inbound): ?JsonResponse
    {
        $token = config('sandbox.token');

        if (! is_string($token) || blank($token)) {
            return response()->json([
                'message' => 'Sandbox inbound processing is unavailable.',
            ], 503);
        }

        $tokenHeader = (string) config('sandbox.token_header', 'X-Sandbox-Token');
        $incomingToken = (string) $request->header($tokenHeader, '');

        if (! hash_equals($token, $incomingToken)) {
            return response()->json([
                'message' => 'Sandbox token is invalid.',
            ], 401);
        }

        $request->headers->remove($tokenHeader);

        return null;
    }

    public function early(Request $request, HookInbound $inbound): ?JsonResponse
    {
        return null;
    }

    public function respond(Request $request, HookInbound $inbound): ?JsonResponse
    {
        $payload = $request->all();

        return response()->json([
            'message' => 'Sandbox intake received for inventory adjustment processing.',
            'contract' => 'inventory.adjustment.v1',
            'correlation_id' => $payload['correlation_id'] ?? null,
            'inbound' => [
                'id' => $inbound->id,
                'slug' => $inbound->slug,
                'name' => $inbound->name,
            ],
        ]);
    }
}
