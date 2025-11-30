<?php

namespace Modules\Core\App\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Centralized service for registering module permissions.
 *
 * Provides a standardized way for modules to register their permissions
 * with automatic prefixing, default role assignments, and cache management.
 */
class PermissionRegistry
{
    /**
     * Register permissions for a module.
     *
     * Permissions will be automatically prefixed with the module name.
     * Format: {module}.{resource}.{action}
     *
     * @param  string  $module  Module name (e.g., 'blog', 'newsletter')
     * @param  array<string>  $permissions  Array of permission names without module prefix (e.g., ['posts.view', 'posts.create'])
     * @param  array<string, array<string>>  $defaultRoleAssignments  Optional default role assignments (e.g., ['Administrator' => ['posts.*']])
     */
    public function register(string $module, array $permissions, array $defaultRoleAssignments = []): void
    {
        $module = strtolower($module);

        if (empty($permissions)) {
            Log::warning('PermissionRegistry: Empty permissions array provided', [
                'module' => $module,
            ]);

            return;
        }

        $permissions = array_filter($permissions, function ($permission) {
            return is_string($permission) && trim($permission) !== '';
        });

        if (empty($permissions)) {
            Log::warning('PermissionRegistry: No valid permission names provided', [
                'module' => $module,
            ]);

            return;
        }

        $createdPermissions = [];

        foreach ($permissions as $permission) {
            $fullPermissionName = $this->buildPermissionName($module, $permission);

            try {
                $permissionModel = Permission::firstOrCreate(['name' => $fullPermissionName]);
                $createdPermissions[] = $permissionModel;
            } catch (\Exception $e) {
                Log::error("Failed to create permission: {$fullPermissionName}", [
                    'module' => $module,
                    'permission' => $permission,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! empty($defaultRoleAssignments)) {
            $this->assignToDefaultRoles($createdPermissions, $defaultRoleAssignments, $module);
        }

        app(PermissionCacheService::class)->clear();
    }

    /**
     * Build full permission name with module prefix.
     *
     * @param  string  $module  Module name
     * @param  string  $permission  Permission name (may already include module prefix)
     * @return string Full permission name in format {module}.{resource}.{action}
     */
    private function buildPermissionName(string $module, string $permission): string
    {
        if (str_starts_with($permission, $module.'.')) {
            return $permission;
        }

        return $module.'.'.$permission;
    }

    /**
     * Assign permissions to default roles.
     *
     * Supports wildcard patterns (e.g., 'posts.*' matches all post permissions).
     * Patterns are automatically prefixed with the module name.
     *
     * @param  array<Permission>  $permissions  Created permission models
     * @param  array<string, array<string>>  $roleAssignments  Role name => array of permission patterns
     * @param  string  $module  Module name for building full permission names
     */
    private function assignToDefaultRoles(array $permissions, array $roleAssignments, string $module): void
    {
        if (empty($permissions)) {
            Log::warning('PermissionRegistry: No permissions to assign', [
                'module' => $module,
            ]);

            return;
        }

        foreach ($roleAssignments as $roleName => $permissionPatterns) {
            if (! is_string($roleName) || trim($roleName) === '') {
                Log::warning('PermissionRegistry: Invalid role name', [
                    'module' => $module,
                    'role_name' => $roleName,
                ]);

                continue;
            }

            if (! is_array($permissionPatterns) || empty($permissionPatterns)) {
                Log::warning('PermissionRegistry: Invalid permission patterns for role', [
                    'module' => $module,
                    'role' => $roleName,
                ]);

                continue;
            }

            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                Log::warning("Role not found for permission assignment: {$roleName}");

                continue;
            }

            $permissionsToAssign = [];

            foreach ($permissionPatterns as $pattern) {
                if (! is_string($pattern) || trim($pattern) === '') {
                    Log::warning('PermissionRegistry: Invalid permission pattern', [
                        'module' => $module,
                        'role' => $roleName,
                        'pattern' => $pattern,
                    ]);

                    continue;
                }

                if (str_ends_with($pattern, '.*')) {
                    $prefix = rtrim($pattern, '.*');

                    if (str_contains($prefix, '.*')) {
                        Log::warning('PermissionRegistry: Invalid wildcard pattern (nested wildcards)', [
                            'module' => $module,
                            'role' => $roleName,
                            'pattern' => $pattern,
                        ]);

                        continue;
                    }

                    $fullPrefix = $this->buildPermissionName($module, $prefix);

                    foreach ($permissions as $permission) {
                        if (str_starts_with($permission->name, $fullPrefix.'.')) {
                            $permissionsToAssign[] = $permission->name;
                        }
                    }
                } else {
                    $fullPermissionName = $this->buildPermissionName($module, $pattern);
                    $permissionsToAssign[] = $fullPermissionName;
                }
            }

            if (! empty($permissionsToAssign)) {
                try {
                    $role->givePermissionTo(array_unique($permissionsToAssign));
                } catch (\Exception $e) {
                    Log::error("Failed to assign permissions to role: {$roleName}", [
                        'permissions' => $permissionsToAssign,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
