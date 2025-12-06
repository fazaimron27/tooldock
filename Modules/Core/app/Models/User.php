<?php

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\AuditLog\App\Traits\LogsActivity;
use Modules\Core\Database\Factories\UserFactory;
use Modules\Groups\App\Traits\HasGroups;
use Modules\Media\Models\MediaFile;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Modules\Core\Database\Factories\UserFactory> */
    use HasFactory, HasGroups, HasRoles, HasUuids, LogsActivity, Notifiable;

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
     * Determine if the user has the given permission.
     *
     * Overrides Spatie's hasPermissionTo to also check group permissions.
     * This ensures that policies calling hasPermissionTo() will also check
     * group-based permissions, not just role-based permissions.
     *
     * @param  string|\Spatie\Permission\Contracts\Permission  $permission
     * @param  string|null  $guardName
     * @return bool
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $permissionName = is_string($permission) ? $permission : (is_object($permission) ? $permission->name : null);

        if (! $permissionName) {
            return $this->hasPermissionToViaRole($permission, $guardName);
        }

        if (method_exists($this, 'hasGroupPermission') && $this->hasGroupPermission($permissionName)) {
            return true;
        }

        return $this->hasPermissionToViaRole($permission, $guardName);
    }

    /**
     * Check permission using Spatie's standard method (roles only).
     *
     * This calls the original hasPermissionTo from HasPermissions trait
     * without causing infinite recursion.
     *
     * @param  string|\Spatie\Permission\Contracts\Permission  $permission
     * @param  string|null  $guardName
     * @return bool
     */
    private function hasPermissionToViaRole($permission, $guardName = null): bool
    {
        if ($this->getWildcardClass()) {
            return $this->hasWildcardPermission($permission, $guardName);
        }

        $permission = $this->filterPermission($permission, $guardName);

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return UserFactory
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * Get the user's avatar.
     */
    public function avatar(): MorphOne
    {
        return $this->morphOne(MediaFile::class, 'model');
    }
}
