<?php

namespace Modules\Sandbox\Services\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Hook\Models\HookInboundRequest;
use Modules\Sandbox\Jobs\ApplyInventoryAdjustmentJob;
use Modules\Sandbox\Jobs\ReviewInventoryAdjustmentJob;
use Modules\Sandbox\Models\SandboxIntake;
use Modules\Sandbox\Services\SandboxPayloadProcessingService;

class SandboxInboundReceivedHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly SandboxPayloadProcessingService $payloadProcessor,
    ) {}

    public function getEvents(): array
    {
        return ['hook.webhook.received'];
    }

    public function getModule(): string
    {
        return 'Sandbox';
    }

    public function getName(): string
    {
        return 'sandbox_inbound_received';
    }

    public function supports(string $event, mixed $data): bool
    {
        if (blank(config('sandbox.token'))) {
            return false;
        }

        if (! is_array($data) || ! ($data['request'] ?? null) instanceof HookInboundRequest) {
            return false;
        }

        $request = $data['request'];
        $probeHeader = strtolower((string) config('sandbox.probe_header', 'X-Sandbox-Probe'));
        $headers = $request->headers ?? [];

        return array_key_exists($probeHeader, array_change_key_case($headers, CASE_LOWER));
    }

    public function handle(mixed $data): ?array
    {
        /** @var HookInboundRequest $request */
        $request = $data['request'];
        $inbound = $request->inbound ?? $request->load('inbound')->inbound;

        if (! $inbound) {
            return null;
        }

        $cacheKey = (string) config('sandbox.cache_key', 'sandbox:inbound:entries');
        $maxEntries = (int) config('sandbox.max_entries', 50);
        $processing = $this->payloadProcessor->process($request->payload);
        $output = is_array($processing['output'] ?? null) ? $processing['output'] : [];
        $routing = is_array($output['routing'] ?? null) ? $output['routing'] : [];
        $metrics = is_array($output['metrics'] ?? null) ? $output['metrics'] : null;
        $riskFlags = is_array($output['risk_flags'] ?? null) ? $output['risk_flags'] : null;
        $normalizedItems = is_array($output['normalized_items'] ?? null) ? $output['normalized_items'] : null;
        $validationErrors = is_array($output['errors'] ?? null) ? $output['errors'] : null;
        $routingQueue = is_string($routing['queue'] ?? null) ? $routing['queue'] : null;
        $correlationId = is_string($output['correlation_id'] ?? null) ? $output['correlation_id'] : null;

        $intakeValues = [
            'user_id' => (string) $inbound->user_id,
            'inbound_id' => (string) $inbound->id,
            'inbound_request_id' => (string) $request->id,
            'event' => (string) ($output['event'] ?? 'inventory.adjustment'),
            'correlation_id' => $correlationId,
            'occurred_at' => $output['occurred_at'] ?? null,
            'warehouse_code' => $output['warehouse_code'] ?? null,
            'status' => (string) ($output['status'] ?? ($processing['ok'] ? 'accepted' : 'rejected')),
            'priority' => $routing['priority'] ?? null,
            'routing_queue' => $routingQueue,
            'requires_manual_review' => (bool) ($routing['requires_manual_review'] ?? false),
            'risk_flags' => $riskFlags,
            'metrics' => $metrics,
            'normalized_items' => $normalizedItems,
            'validation_errors' => $validationErrors,
            'processing' => $processing,
            'payload' => $request->payload,
            'received_at' => CarbonImmutable::now(),
            'processed_at' => CarbonImmutable::now(),
            'failure_reason' => null,
        ];

        $intake = $correlationId !== null
            ? SandboxIntake::query()->firstOrCreate([
                'inbound_id' => $inbound->id,
                'correlation_id' => $correlationId,
            ], $intakeValues)
            : SandboxIntake::query()->updateOrCreate(['inbound_request_id' => $request->id], $intakeValues);

        if ($intake->wasRecentlyCreated && $processing['ok'] && $routingQueue === config('sandbox.review_queue', 'sandbox-review')) {
            $intake->update(['status' => 'queued']);
            ReviewInventoryAdjustmentJob::dispatch($intake->id);
        }

        if ($intake->wasRecentlyCreated && $processing['ok'] && $routingQueue === config('sandbox.apply_queue', 'sandbox-apply')) {
            $intake->update(['status' => 'queued']);
            ApplyInventoryAdjustmentJob::dispatch($intake->id);
        }

        $entry = [
            'request_id' => $request->id,
            'intake_id' => $intake->id,
            'status' => $intake->status,
            'inbound_id' => $inbound->id,
            'inbound_slug' => $inbound->slug,
            'inbound_name' => $inbound->name,
            'method' => $request->method,
            'source_ip' => $request->source_ip,
            'payload' => $request->payload,
            'processing' => $processing,
            'received_at' => CarbonImmutable::now()->toIso8601String(),
        ];

        $entries = Cache::get($cacheKey, []);
        array_unshift($entries, $entry);
        $entries = array_slice($entries, 0, max(1, $maxEntries));

        Cache::put($cacheKey, $entries, now()->addDay());

        return [
            'type' => 'info',
            'title' => 'Sandbox probe received',
            'message' => "{$inbound->name} ← {$request->source_ip} ({$processing['action']})",
            'url' => route('sandbox.index'),
            'category' => 'sandbox_inbound',
            'delivery' => 'flash',
        ];
    }
}
