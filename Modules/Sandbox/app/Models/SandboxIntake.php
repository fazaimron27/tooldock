<?php

namespace Modules\Sandbox\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Hook\Models\HookInbound;
use Modules\Hook\Models\HookInboundRequest;

class SandboxIntake extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'sandbox_intakes';

    protected $fillable = [
        'user_id',
        'inbound_id',
        'inbound_request_id',
        'event',
        'correlation_id',
        'occurred_at',
        'warehouse_code',
        'status',
        'priority',
        'routing_queue',
        'requires_manual_review',
        'risk_flags',
        'metrics',
        'normalized_items',
        'validation_errors',
        'processing',
        'payload',
        'received_at',
        'processed_at',
        'applied_at',
        'reviewed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'risk_flags' => 'array',
            'metrics' => 'array',
            'normalized_items' => 'array',
            'validation_errors' => 'array',
            'processing' => 'array',
            'payload' => 'array',
            'requires_manual_review' => 'boolean',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'applied_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(HookInbound::class, 'inbound_id');
    }

    public function inboundRequest(): BelongsTo
    {
        return $this->belongsTo(HookInboundRequest::class, 'inbound_request_id');
    }
}
