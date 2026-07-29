<?php

/**
 * BotPlatform Model
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Bot\Enums\BotDriver;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;

/**
 * @property string $id
 * @property string $user_id
 * @property BotDriver $driver
 * @property string $name
 * @property array $credentials
 * @property string|null $hook_inbound_slug
 * @property bool $is_active
 * @property Carbon|null $tested_at
 */
class BotPlatform extends Model
{
    use HasFactory, HasUserOwnership, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'bot_platforms';

    protected $fillable = [
        'user_id',
        'driver',
        'name',
        'credentials',
        'is_active',
        'tested_at',
        'hook_inbound_slug',
    ];

    protected function casts(): array
    {
        return [
            'driver' => BotDriver::class,
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'tested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BotMessage::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(BotConnection::class);
    }

    public function getAuditTags(): array
    {
        return ['bot'];
    }
}
