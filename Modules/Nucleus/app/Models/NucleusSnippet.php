<?php

/**
 * NucleusSnippet Model
 *
 * Eloquent model representing a saved JSON snippet in the Nucleus editor.
 * Supports UUID primary keys, user ownership, and audit logging.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;
use Modules\Nucleus\Database\Factories\NucleusSnippetFactory;
use Modules\Nucleus\Policies\NucleusSnippetPolicy;

/**
 * Class NucleusSnippet
 *
 * Stores JSON templates or data snapshots saved by users in the Nucleus
 * editor for quick access and reuse.
 *
 * @property string $id
 * @property string $user_id
 * @property string $title
 * @property string|null $raw_json
 *
 * @see NucleusSnippetPolicy
 */
class NucleusSnippet extends Model
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
    protected $table = 'nucleus_snippets';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'raw_json',
    ];

    /**
     * Attributes that must be redacted from audit log payloads.
     *
     * @var list<string>
     */
    protected $auditSensitive = [
        'raw_json',
    ];

    /**
     * Get the user that owns the snippet.
     *
     * @return BelongsTo The user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get audit tags for this snippet.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        return ['nucleus', 'snippet'];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory The NucleusSnippet factory instance
     */
    protected static function newFactory(): Factory
    {
        return NucleusSnippetFactory::new();
    }
}
