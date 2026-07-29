<?php

/**
 * HookOutbound Model
 *
 * Eloquent model representing an outbound webhook configuration.
 * Sends webhooks to external services with a configured target URL,
 * HTTP method, headers, and payload template.
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
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;
use Modules\Hook\Enums\HookOutboundProvider;

/**
 * Class HookOutbound
 *
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string|null $target_url
 * @property string $method
 * @property HookOutboundProvider $provider
 * @property array|null $provider_config
 * @property array|null $headers
 * @property array|null $payload_template
 * @property string|null $trigger
 * @property string|null $description
 * @property bool $is_active
 * @property string|null $display_url
 */
class HookOutbound extends Model
{
    use HasFactory, HasUserOwnership, HasUuids, LogsActivity, SoftDeletes;

    /** @var string */
    protected $keyType = 'string';

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $table = 'hook_outbounds';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'target_url',
        'method',
        'trigger',
        'provider',
        'provider_config',
        'headers',
        'payload_template',
        'description',
        'is_active',
    ];

    /** @var list<string> Never send encrypted credentials to the frontend. */
    protected $hidden = ['provider_config'];

    /** @var list<string> */
    protected $appends = ['display_url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => HookOutboundProvider::class,
            'provider_config' => 'encrypted:array',
            'headers' => 'array',
            'payload_template' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Safe display URL for the frontend card:
     * - Generic  → raw target_url
     * - Managed  → masked URL (token truncated to last 6 chars)
     */
    public function getDisplayUrlAttribute(): ?string
    {
        $provider = $this->provider instanceof HookOutboundProvider
            ? $this->provider
            : HookOutboundProvider::Generic;

        if ($provider === HookOutboundProvider::Generic) {
            return $this->target_url;
        }

        return $provider->maskedUrl($this->provider_config ?? []);
    }

    /**
     * Get the user that owns this outbound webhook.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all delivery records for this outbound webhook.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(HookOutboundDelivery::class, 'outbound_id');
    }

    /**
     * Get audit tags for this outbound webhook.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        return ['hook'];
    }
}
