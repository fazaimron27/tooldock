<?php

namespace Modules\Groups\App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Groups\App\Services\GroupPermissionCacheService;
use Modules\Groups\Models\Group;

trait HasGroups
{
    /**
     * Get the groups that this user belongs to.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withTimestamps();
    }

    /**
     * Get all permissions the user has through their groups.
     *
     * Collects permissions from both direct group permissions and permissions
     * inherited through group roles.
     *
     * @return array<string>
     */
    public function getGroupPermissions(): array
    {
        $cacheService = app(GroupPermissionCacheService::class);
        $cached = $cacheService->get($this->id);

        if ($cached !== null) {
            return $cached;
        }

        $permissions = $this->groups()
            ->with(['permissions', 'roles.permissions'])
            ->get()
            ->flatMap(function ($group) {
                $directPermissions = $group->permissions->pluck('name');

                $rolePermissions = $group->roles->flatMap(function ($role) {
                    return $role->permissions->pluck('name');
                });

                return $directPermissions->merge($rolePermissions);
            })
            ->unique()
            ->values()
            ->toArray();

        $cacheService->put($this->id, $permissions);

        return $permissions;
    }

    /**
     * Check if the user has a permission through any of their groups.
     *
     * Supports both exact matches and wildcard patterns (e.g., 'categories.*').
     * Uses caching to improve performance for repeated checks.
     *
     * @param  string  $permissionName  The permission name to check (supports wildcards)
     * @return bool
     */
    public function hasGroupPermission(string $permissionName): bool
    {
        if ($this->relationLoaded('groups')) {
            if ($this->groups->isEmpty()) {
                return false;
            }
        } elseif (! $this->groups()->exists()) {
            return false;
        }

        if (str_ends_with($permissionName, '.*')) {
            $prefix = rtrim($permissionName, '.*');
            $groupPermissions = $this->getGroupPermissions();

            foreach ($groupPermissions as $permission) {
                if (str_starts_with($permission, $prefix.'.')) {
                    return true;
                }
            }

            return false;
        }

        $groupPermissions = $this->getGroupPermissions();

        return in_array($permissionName, $groupPermissions, true);
    }
}
