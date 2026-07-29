<?php

/**
 * Hook Controller
 *
 * Renders the main Hook module Inertia page with inbound endpoints and outbound webhooks.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hook\Enums\HookOutboundProvider;
use Modules\Hook\Models\HookInbound;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Services\HookEventRegistry;

/**
 * Class HookController
 *
 * Handles the main page render for the Hook module.
 */
class HookController extends Controller
{
    /**
     * Display the Hook module dashboard with inbound endpoints and outbound webhooks.
     */
    public function index(Request $request, HookEventRegistry $eventRegistry): Response
    {
        $this->authorize('viewAny', HookInbound::class);
        $this->authorize('viewAny', HookOutbound::class);

        $user = $request->user();

        $inbounds = HookInbound::forUser($user)
            ->withCount('inboundRequests')
            ->orderBy('created_at', 'desc')
            ->get();

        $outbounds = HookOutbound::forUser($user)
            ->withCount('deliveries')
            ->orderBy('created_at', 'desc')
            ->get();

        $triggers = collect($eventRegistry->all())
            ->map(fn ($def) => [
                'key' => array_search($def, $eventRegistry->all()),
                'label' => $def['label'],
                'payloadSchema' => $def['payloadSchema'],
            ])
            ->values();

        return Inertia::render('Modules::Hook/Index', [
            'inbounds' => $inbounds,
            'outbounds' => $outbounds,
            'receiveBaseUrl' => url('/api/v1/hook/inbound'),
            'triggers' => $triggers,
            'providers' => HookOutboundProvider::options(),
        ]);
    }
}
