<?php

/**
 * HookInbound Model
 *
 * Eloquent model representing an inbound webhook endpoint.
 * Receives webhooks from external services and stores them for inspection.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;

/**
 * Class HookInbound
 *
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 */
class HookInbound extends Model
{
    use HasFactory, HasUserOwnership, HasUuids, LogsActivity, SoftDeletes;

    /** @var string */
    protected $keyType = 'string';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $table = 'hook_inbounds';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    /** @var array<string, mixed> Model attribute defaults. */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot the model and auto-generate slug on creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (HookInbound $inbound) {
            if (empty($inbound->slug)) {
                $inbound->slug = Str::random(12);
            }
        });
    }

    /**
     * Get the user that owns this inbound endpoint.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all received requests for this inbound endpoint.
     */
    public function inboundRequests(): HasMany
    {
        return $this->hasMany(HookInboundRequest::class, 'inbound_id');
    }

    /**
     * Get audit tags for this inbound endpoint.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        return ['hook'];
    }
}
