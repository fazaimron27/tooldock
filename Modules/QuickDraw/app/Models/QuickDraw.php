<?php

/**
 * QuickDraw Model
 *
 * Eloquent model representing an infinite whiteboard canvas.
 * Supports UUID primary keys, user ownership, and audit logging.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\QuickDraw\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;

/**
 * Class QuickDraw
 *
 * Stores metadata for a whiteboard canvas. The actual tldraw document
 * state is stored in the related QuickDrawState model.
 *
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string|null $description
 *
 * @see \Modules\QuickDraw\Policies\QuickDrawPolicy
 */
class QuickDraw extends Model
{
    use HasFactory, HasUserOwnership, HasUuids, LogsActivity;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quickdraws';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * Get the user that owns the canvas.
     *
     * @return BelongsTo The user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tldraw document state for this canvas.
     *
     * @return HasOne The state relationship
     */
    public function state(): HasOne
    {
        return $this->hasOne(QuickDrawState::class, 'quickdraw_id');
    }

    /**
     * Get audit tags for this canvas.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        return ['quickdraw'];
    }
}
