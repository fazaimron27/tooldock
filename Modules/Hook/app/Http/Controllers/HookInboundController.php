<?php

/**
 * HookInboundController
 *
 * Manages inbound webhook endpoint CRUD and the public receive endpoint.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\HookInboundProcessorRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Hook\Http\Requests\StoreInboundRequest;
use Modules\Hook\Http\Requests\UpdateInboundRequest;
use Modules\Hook\Jobs\ProcessInboundWebhookJob;
use Modules\Hook\Models\HookInbound;
use Modules\Hook\Models\HookInboundRequest;
use Modules\Hook\Policies\HookInboundPolicy;

/**
 * Class HookInboundController
 *
 * @see HookInboundPolicy
 */
class HookInboundController extends Controller
{
    public function __construct(
        private readonly HookInboundProcessorRegistry $processorRegistry,
    ) {}

    /**
     * Show a single inbound endpoint with its received requests.
     *
     * @param  HookInbound  $inbound
     * @return Response
     */
    public function show(HookInbound $inbound): Response
    {
        $this->authorize('view', $inbound);

        $requests = $inbound->inboundRequests()
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Modules::Hook/Inbound/Show', [
            'inbound' => $inbound->loadCount('inboundRequests'),
            'requests' => $requests,
            'receiveBaseUrl' => url('/api/v1/hook/inbound'),
        ]);
    }

    /**
     * Create a new inbound webhook endpoint.
     *
     * @param  StoreInboundRequest  $request
     * @return JsonResponse
     */
    public function store(StoreInboundRequest $request): RedirectResponse
    {
        HookInbound::create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return back();
    }

    /**
     * Update an inbound webhook endpoint.
     *
     * @param  UpdateInboundRequest  $request
     * @param  HookInbound  $inbound
     * @return RedirectResponse
     */
    public function update(UpdateInboundRequest $request, HookInbound $inbound): RedirectResponse
    {
        $inbound->update($request->validated());

        return back();
    }

    /**
     * Delete an inbound webhook endpoint (soft delete).
     *
     * @param  HookInbound  $inbound
     * @return JsonResponse
     */
    public function destroy(HookInbound $inbound): RedirectResponse
    {
        $this->authorize('delete', $inbound);

        $inbound->delete();

        return back();
    }

    /**
     * Get received requests for an inbound endpoint.
     *
     * @param  HookInbound  $inbound
     * @return JsonResponse
     */
    public function requests(HookInbound $inbound): JsonResponse
    {
        $this->authorize('view', $inbound);

        $requests = $inbound->inboundRequests()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'requests' => $requests,
        ]);
    }

    /**
     * Public endpoint to receive incoming webhooks.
     *
     * Accepts any HTTP method. Stores the raw request data and
     * dispatches a job to broadcast the real-time UI update.
     *
     * @param  Request  $request
     * @param  string  $slug
     * @return JsonResponse
     */
    public function receive(Request $request, string $slug): JsonResponse
    {
        $inbound = HookInbound::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $inbound) {
            return response()->json([
                'message' => 'Inbound endpoint not found or inactive.',
            ], 404);
        }

        // Delegate to registered processors (e.g. Discord Ed25519, PING/PONG).
        // intercept() returns a Response to short-circuit, or null to continue.
        if ($intercept = $this->processorRegistry->intercept($request, $inbound)) {
            return $intercept;
        }

        $inboundRequest = HookInboundRequest::create([
            'inbound_id' => $inbound->id,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'payload' => $request->all() ?: null,
            'query_params' => $request->query() ?: null,
            'source_ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
        ]);

        ProcessInboundWebhookJob::dispatch($inboundRequest, $inbound->user_id);

        // Allow the matched processor to override the response (e.g. Discord -> {"type":5}).
        return $this->processorRegistry->respond($request, $inbound)
            ?? response()->json(['message' => 'Webhook received.', 'id' => $inboundRequest->id]);
    }

    /**
     * Sanitize request headers for JSON storage.
     *
     * Flattens single-value arrays to strings for readability.
     *
     * @param  array<string, array<string>>  $headers
     * @return array<string, mixed>
     */
    private function sanitizeHeaders(array $headers): array
    {
        return collect($headers)->map(function ($values) {
            return count($values) === 1 ? $values[0] : $values;
        })->toArray();
    }
}
