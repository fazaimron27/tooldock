<?php

namespace Modules\Groups\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\AuditLog\App\Traits\LogsActivity;
use Modules\Core\App\Models\Permission;
use Modules\Core\App\Models\Role;
use Modules\Core\App\Models\User;
use Modules\Groups\Database\Factories\GroupFactory;

class Group extends Model
{
    use HasFactory, HasUuids, LogsActivity;

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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });

        static::updating(function ($group) {
            if ($group->isDirty('name') && empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return GroupFactory::new();
    }

    /**
     * Get the users that belong to this group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withTimestamps();
    }

    /**
     * Get the permissions assigned to this group.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'group_has_permissions')
            ->withTimestamps();
    }

    /**
     * Get the roles assigned to this group.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'group_has_roles')
            ->withTimestamps();
    }

    /**
     * Assign a role to this group.
     *
     * @param  Role|string  $role  Role model, ID, or name
     * @return void
     */
    public function assignRole($role): void
    {
        if (is_string($role) && ! $role instanceof Role) {
            $role = Role::findByName($role) ?? Role::find($role);
        }

        if ($role && ! $this->roles()->where('roles.id', $role->id)->exists()) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Remove a role from this group.
     *
     * @param  Role|string  $role  Role model, ID, or name
     * @return void
     */
    public function removeRole($role): void
    {
        if (is_string($role) && ! $role instanceof Role) {
            $role = Role::findByName($role) ?? Role::find($role);
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Check if the group has a specific permission.
     *
     * Checks both direct permissions and permissions through roles.
     *
     * @param  string|\Spatie\Permission\Contracts\Permission  $permission
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        $permissionName = is_string($permission) ? $permission : (is_object($permission) ? $permission->name : null);

        if (! $permissionName) {
            return false;
        }

        // Check direct permissions
        $this->loadMissing('permissions');
        if ($this->permissions->contains('name', $permissionName)) {
            return true;
        }

        // Check permissions through roles
        $this->loadMissing('roles.permissions');
        foreach ($this->roles as $role) {
            if ($role->hasPermissionTo($permissionName)) {
                return true;
            }
        }

        return false;
    }
}
