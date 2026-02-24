<?php

/**
 * Folio Data Model
 *
 * Eloquent model representing the resume JSON content.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class FolioData
 *
 * Stores the JSON resume content for persistence.
 * Has a 1:1 relationship with Folio via unique folio_id.
 *
 * @property string $id
 * @property string $folio_id
 * @property array $content
 */
class FolioData extends Model
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
    protected $table = 'folio_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'folio_id',
        'content',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    /**
     * Get the resume that owns this data.
     *
     * @return BelongsTo The folio relationship
     */
    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }
}
