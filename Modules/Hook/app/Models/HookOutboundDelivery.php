<?php

/**
 * HookOutboundDelivery Model
 *
 * Eloquent model representing the result of an outbound webhook delivery,
 * storing response status, headers, body, duration, and any error.
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
 * Class HookOutboundDelivery
 *
 * @property string $id
 * @property string $outbound_id
 * @property int|null $response_status
 * @property array|null $response_headers
 * @property string|null $response_body
 * @property int|null $duration_ms
 * @property string|null $error_message
 */
class HookOutboundDelivery extends Model
{
    use HasFactory, HasUuids;

    /** @var string */
    protected $keyType = 'string';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $table = 'hook_outbound_deliveries';

    /** @var list<string> */
    protected $fillable = [
        'outbound_id',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_headers' => 'array',
            'response_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * Get the outbound webhook that created this delivery.
     */
    public function outbound(): BelongsTo
    {
        return $this->belongsTo(HookOutbound::class, 'outbound_id');
    }
}
