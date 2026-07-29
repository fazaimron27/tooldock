<?php

/**
 * BotConnection
 *
 * Links a platform user (Discord member ID, Telegram user ID) to a Tool Dock
 * user account. Created when a user runs /start and confirms on the web.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

class BotConnection extends Model
{
    use HasUuids;

    protected $fillable = [
        'bot_platform_id',
        'platform_user_id',
        'platform_username',
        'user_id',
    ];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(BotPlatform::class, 'bot_platform_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
