<?php

/**
 * HookInboundRequest Model
 *
 * Eloquent model representing a received inbound webhook request,
 * storing method, headers, payload, query params, and source IP.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class HookInboundRequest
 *
 * @property string $id
 * @property string $inbound_id
 * @property string $method
 * @property string $url
 * @property array|null $headers
 * @property array|null $payload
 * @property array|null $query_params
 * @property string $source_ip
 * @property string|null $content_type
 */
class HookInboundRequest extends Model
{
    use HasFactory, HasUuids;

    /** @var string */
    protected $keyType = 'string';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $table = 'hook_inbound_requests';

    /** @var list<string> */
    protected $fillable = [
        'inbound_id',
        'method',
        'url',
        'headers',
        'payload',
        'query_params',
        'source_ip',
        'content_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'query_params' => 'array',
        ];
    }

    /**
     * Get the inbound endpoint that received this request.
     */
    public function inbound(): BelongsTo
    {
        return $this->belongsTo(HookInbound::class, 'inbound_id');
    }
}
