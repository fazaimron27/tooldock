<?php

/**
 * QuickDraw State Model
 *
 * Eloquent model representing the tldraw document state snapshot for a canvas.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\QuickDraw\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class QuickDrawState
 *
 * Stores the JSON snapshot of the tldraw editor store for persistence.
 * Has a 1:1 relationship with QuickDraw via unique quickdraw_id.
 *
 * @property string $id
 * @property string $quickdraw_id
 * @property array $document_state
 */
class QuickDrawState extends Model
{
    use HasFactory, HasUuids;

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
    protected $table = 'quickdraw_states';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'quickdraw_id',
        'document_state',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * document_state is intentionally NOT cast to array.
     * PHP's json_encode converts empty JS objects {} to arrays [],
     * which corrupts the tldraw snapshot on round-trip.
     * We store and return it as a raw JSON string.
     */

    /**
     * Get the canvas that owns this state.
     *
     * @return BelongsTo The quickdraw relationship
     */
    public function quickdraw(): BelongsTo
    {
        return $this->belongsTo(QuickDraw::class);
    }
}
