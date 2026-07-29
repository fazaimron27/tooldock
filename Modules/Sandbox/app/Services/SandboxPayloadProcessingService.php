<?php

namespace Modules\Sandbox\Services;

use Illuminate\Support\Facades\Validator;

class SandboxPayloadProcessingService
{
    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    public function process(?array $payload): array
    {
        $payload = $payload ?? [];
        $validator = Validator::make($payload, [
            'event' => ['required', 'in:inventory.adjustment'],
            'correlation_id' => ['required', 'uuid'],
            'occurred_at' => ['required', 'date'],
            'warehouse' => ['required', 'array:code'],
            'warehouse.code' => ['required', 'string', 'max:32'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:sku,delta,unit_cost'],
            'items.*.sku' => ['required', 'string', 'max:64'],
            'items.*.delta' => ['required', 'integer', 'not_in:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return [
                'action' => 'inventory_adjustment_intake',
                'ok' => false,
                'summary' => 'Rejected: payload does not match inventory.adjustment contract.',
                'output' => [
                    'status' => 'rejected',
                    'contract' => 'inventory.adjustment.v1',
                    'errors' => $validator->errors()->toArray(),
                ],
            ];
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();
        $items = collect($validated['items']);

        $normalizedItems = $items
            ->map(function (array $item): array {
                return [
                    'sku' => (string) $item['sku'],
                    'delta' => (int) $item['delta'],
                    'unit_cost' => (float) $item['unit_cost'],
                    'line_value_impact' => (int) $item['delta'] * (float) $item['unit_cost'],
                ];
            })
            ->values();

        $totalDelta = $normalizedItems->sum('delta');
        $absoluteUnits = $normalizedItems->sum(fn (array $item) => abs($item['delta']));
        $valueImpact = $normalizedItems->sum('line_value_impact');
        $hasPositive = $normalizedItems->contains(fn (array $item) => $item['delta'] > 0);
        $hasNegative = $normalizedItems->contains(fn (array $item) => $item['delta'] < 0);

        $direction = $hasPositive && $hasNegative
            ? 'mixed'
            : ($hasPositive ? 'inbound' : 'outbound');

        $priority = $absoluteUnits >= 100
            ? 'high'
            : ($absoluteUnits >= 20 ? 'medium' : 'low');

        $riskFlags = [];

        if ($direction === 'mixed') {
            $riskFlags[] = 'mixed_direction';
        }

        if ($absoluteUnits >= 100) {
            $riskFlags[] = 'large_unit_movement';
        }

        if (abs($valueImpact) >= 10000000) {
            $riskFlags[] = 'high_value_impact';
        }

        $requiresManualReview = ! empty($riskFlags);

        return [
            'action' => 'inventory_adjustment_intake',
            'ok' => true,
            'summary' => $requiresManualReview
                ? 'Accepted with review required before apply.'
                : 'Accepted and ready for apply queue.',
            'output' => [
                'status' => 'accepted',
                'contract' => 'inventory.adjustment.v1',
                'correlation_id' => $validated['correlation_id'],
                'event' => $validated['event'],
                'occurred_at' => $validated['occurred_at'],
                'warehouse_code' => $validated['warehouse']['code'],
                'metrics' => [
                    'line_count' => $normalizedItems->count(),
                    'total_delta' => $totalDelta,
                    'absolute_units' => $absoluteUnits,
                    'value_impact' => $valueImpact,
                    'direction' => $direction,
                ],
                'risk_flags' => $riskFlags,
                'routing' => [
                    'priority' => $priority,
                    'queue' => $requiresManualReview
                        ? (string) config('sandbox.review_queue', 'sandbox-review')
                        : (string) config('sandbox.apply_queue', 'sandbox-apply'),
                    'requires_manual_review' => $requiresManualReview,
                ],
                'normalized_items' => $normalizedItems->all(),
            ],
        ];
    }
}
