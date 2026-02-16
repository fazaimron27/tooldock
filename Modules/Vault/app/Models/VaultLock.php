<?php

/**
 * Vault Lock Model
 *
 * Eloquent model representing a user's vault PIN lock record.
 * Stores the hashed PIN used for vault access protection.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * Class VaultLock
 *
 * Each user has at most one VaultLock record containing their hashed PIN.
 * The PIN is used by VaultLockMiddleware and VaultLockController to
 * enforce session-based vault access control.
 *
 * @property string $id
 * @property string $user_id
 * @property string $pin_hash
 *
 * @see \Modules\Vault\Http\Middleware\VaultLockMiddleware
 * @see \Modules\Vault\Http\Controllers\VaultLockController
 */
class VaultLock extends Model
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
    protected $table = 'vault_locks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'pin_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pin_hash' => 'hashed',
        ];
    }

    /**
     * Get the user that owns the vault lock.
     *
     * @return BelongsTo The user relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
