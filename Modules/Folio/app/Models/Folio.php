<?php

/**
 * Folio Model
 *
 * Eloquent model representing a resume document.
 * Supports UUID primary keys, user ownership, and audit logging.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;

/**
 * Class Folio
 *
 * Stores metadata for a resume document. The actual resume
 * content is stored as JSON in the related FolioData model.
 *
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $slug
 *
 * @see \Modules\Folio\Policies\FolioPolicy
 */
class Folio extends Model
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
    protected $table = 'folios';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
    ];

    /**
     * Get the user that owns the resume.
     *
     * @return BelongsTo The user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the resume content data.
     *
     * @return HasOne The data relationship
     */
    public function data(): HasOne
    {
        return $this->hasOne(FolioData::class, 'folio_id');
    }

    /**
     * Get audit tags for this resume.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        return ['folio'];
    }
}
