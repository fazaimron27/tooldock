<?php

/**
 * BotMessage Model
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Bot\Enums\BotMessageDirection;
use Modules\Bot\Enums\BotMessageStatus;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;

/**
 * @property string $id
 * @property string $user_id
 * @property string $bot_platform_id
 * @property BotMessageDirection $direction
 * @property string|null $command_key
 * @property array|null $raw_payload
 * @property BotMessageStatus $status
 * @property string|null $error_message
 */
class BotMessage extends Model
{
    use HasFactory, HasUserOwnership, HasUuids, SoftDeletes;

    protected $table = 'bot_messages';

    protected $fillable = [
        'user_id',
        'bot_platform_id',
        'direction',
        'command_key',
        'raw_payload',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'direction' => BotMessageDirection::class,
            'status' => BotMessageStatus::class,
            'raw_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(BotPlatform::class, 'bot_platform_id');
    }
}
