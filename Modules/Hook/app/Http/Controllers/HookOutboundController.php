<?php

/**
 * HookOutboundController
 *
 * Manages outbound webhook CRUD and the send/delivery endpoints.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hook\Enums\HookOutboundProvider;
use Modules\Hook\Http\Requests\StoreOutboundRequest;
use Modules\Hook\Http\Requests\UpdateOutboundRequest;
use Modules\Hook\Jobs\SendOutboundWebhookJob;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Policies\HookOutboundPolicy;
use Modules\Hook\Services\HookEventRegistry;

/**
 * Class HookOutboundController
 *
 * @see HookOutboundPolicy
 */
class HookOutboundController extends Controller
{
    /**
     * Show a single outbound webhook with its delivery history.
     *
     * @param  HookOutbound  $outbound
     * @return Response
     */
    public function show(HookOutbound $outbound, HookEventRegistry $eventRegistry): Response
    {
        $this->authorize('view', $outbound);

        $deliveries = $outbound->deliveries()
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $triggerDef = $outbound->trigger
            ? ($eventRegistry->all()[$outbound->trigger] ?? null)
            : null;

        $triggers = collect($eventRegistry->all())
            ->map(fn ($def) => [
                'key' => array_search($def, $eventRegistry->all()),
                'label' => $def['label'],
                'payloadSchema' => $def['payloadSchema'],
            ])
            ->values();

        return Inertia::render('Modules::Hook/Outbound/Show', [
            'outbound' => $outbound->loadCount('deliveries'),
            'deliveries' => $deliveries,
            'triggerDef' => $triggerDef,
            'triggers' => $triggers,
            'providers' => HookOutboundProvider::options(),
        ]);
    }

    /**
     * Create a new outbound webhook configuration.
     *
     * @param  StoreOutboundRequest  $request
     * @return JsonResponse
     */
    public function store(StoreOutboundRequest $request): RedirectResponse
    {
        HookOutbound::create([
            'user_id' => $request->user()->id,
            'method' => $request->validated('method', 'POST'),
            ...$request->safe()->except('method'),
        ]);

        return back();
    }

    /**
     * Update an outbound webhook configuration.
     *
     * Returns JSON when the request expects it (e.g. axios Save),
     * otherwise redirects back for Inertia form submissions.
     *
     * @param  UpdateOutboundRequest  $request
     * @param  HookOutbound  $outbound
     * @return JsonResponse|RedirectResponse
     */
    public function update(UpdateOutboundRequest $request, HookOutbound $outbound): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $outbound);

        $outbound->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Outbound webhook updated.']);
        }

        return back();
    }

    /**
     * Delete an outbound webhook (soft delete).
     *
     * @param  HookOutbound  $outbound
     * @return JsonResponse
     */
    public function destroy(HookOutbound $outbound): RedirectResponse
    {
        $this->authorize('delete', $outbound);

        $outbound->delete();

        return back();
    }

    /**
     * Send the outbound webhook with optional per-fire header/payload overrides.
     *
     * The target URL and HTTP method are fixed outbound webhook config.
     * Only headers and payload can be overridden for each test send.
     *
     * @param  Request  $request
     * @param  HookOutbound  $outbound
     * @return JsonResponse
     */
    public function send(Request $request, HookOutbound $outbound): JsonResponse
    {
        $this->authorize('send', $outbound);

        if (! $outbound->is_active) {
            return response()->json([
                'message' => 'This outbound webhook is inactive and cannot be triggered.',
            ], 403);
        }

        $overrides = $request->validate([
            'headers' => ['nullable', 'array'],
            'payload' => ['nullable', 'array'],
        ]);

        SendOutboundWebhookJob::dispatch(
            $outbound,
            $outbound->user_id,
            $overrides['headers'] ?? null,
            $overrides['payload'] ?? null,
        );

        return response()->json([
            'message' => 'Webhook queued for delivery.',
        ]);
    }

    /**
     * Get delivery history for an outbound webhook.
     *
     * @param  HookOutbound  $outbound
     * @return JsonResponse
     */
    public function deliveries(HookOutbound $outbound): JsonResponse
    {
        $this->authorize('view', $outbound);

        $deliveries = $outbound->deliveries()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'events' => $deliveries,
        ]);
    }
}
