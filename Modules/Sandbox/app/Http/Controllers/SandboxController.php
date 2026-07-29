<?php

namespace Modules\Sandbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hook\Models\HookInbound;
use Modules\Sandbox\Models\SandboxIntake;

class SandboxController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $entries = $this->mapEntriesForUser((string) $user->id);

        $inbounds = HookInbound::forUser($user)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'slug', 'created_at'])
            ->map(fn (HookInbound $inbound) => [
                'id' => $inbound->id,
                'name' => $inbound->name,
                'slug' => $inbound->slug,
                'url' => url('/api/v1/hook/inbound/'.$inbound->slug),
                'created_at' => $inbound->created_at,
            ])
            ->values();

        return Inertia::render('Modules::Sandbox/Index', [
            'entries' => $entries,
            'inbounds' => $inbounds,
            'probeHeader' => config('sandbox.probe_header', 'X-Sandbox-Probe'),
            'tokenHeader' => config('sandbox.token_header', 'X-Sandbox-Token'),
            'tokenEnabled' => filled(config('sandbox.token')),
            'entriesEndpoint' => route('sandbox.entries'),
        ]);
    }

    public function entries(Request $request): JsonResponse
    {
        $entries = $this->mapEntriesForUser((string) $request->user()->id);

        return response()->json([
            'entries' => $entries,
        ]);
    }

    private function mapEntriesForUser(string $userId)
    {
        return SandboxIntake::query()
            ->where('user_id', $userId)
            ->with(['inbound', 'inboundRequest'])
            ->latest('created_at')
            ->limit(25)
            ->get()
            ->map(fn (SandboxIntake $intake) => [
                'request_id' => $intake->inbound_request_id,
                'intake_id' => $intake->id,
                'status' => $intake->status,
                'inbound_id' => $intake->inbound_id,
                'inbound_slug' => $intake->inbound?->slug,
                'inbound_name' => $intake->inbound?->name,
                'method' => $intake->inboundRequest?->method ?? 'POST',
                'source_ip' => $intake->inboundRequest?->source_ip ?? '-',
                'payload' => $intake->payload,
                'processing' => $intake->processing,
                'received_at' => $intake->received_at?->toIso8601String(),
            ])
            ->values();
    }
}
