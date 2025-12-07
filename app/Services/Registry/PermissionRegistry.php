<?php

namespace App\Services\Registry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Services\PermissionCacheService;

/**
 * Centralized service for registering module permissions.
 *
 * Allows modules to register their permissions during service provider boot.
 * Permissions are automatically seeded into the database during module installation
 * and enabling via ModuleLifecycleService, similar to how categories and settings are synced.
 *
 * Provides a standardized way for modules to register their permissions
 * with automatic prefixing, default role assignments, and cache management.
 */
class PermissionRegistry
{
    /**
     * @param  RoleRegistry  $roleRegistry  Registry for role management
     */
    public function __construct(
        private RoleRegistry $roleRegistry
    ) {}

    /**
     * @var array<int, array{
     *     module: string,
     *     permissions: array<string>,
     *     defaultRoleAssignments: array<string, array<string>>
     * }>
     */
    private array $permissionGroups = [];

    /**
     * Track registered permissions by module to prevent duplicates.
     *
     * @var array<string, array<string, bool>> Format: ['module' => ['permission' => true]]
     */
    private array $registeredPermissions = [];

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

        if (! isset($this->registeredPermissions[$module])) {
            $this->registeredPermissions[$module] = [];
        }

        $uniquePermissions = [];
        $duplicateCount = 0;

        foreach ($permissions as $permission) {
            $fullPermissionName = $this->buildPermissionName($module, $permission);

            if (isset($this->registeredPermissions[$module][$fullPermissionName])) {
                Log::warning('PermissionRegistry: Skipping duplicate permission', [
                    'module' => $module,
                    'permission' => $fullPermissionName,
                ]);
                $duplicateCount++;

                continue;
            }

            $this->registeredPermissions[$module][$fullPermissionName] = true;
            $uniquePermissions[] = $permission;
        }

        if (empty($uniquePermissions)) {
            Log::warning('PermissionRegistry: All permissions were duplicates - skipping registration', [
                'module' => $module,
                'total_permissions' => count($permissions),
                'duplicates' => $duplicateCount,
            ]);

            return;
        }

        if ($duplicateCount > 0) {
            Log::info('PermissionRegistry: Registered permissions with some duplicates filtered', [
                'module' => $module,
                'total_permissions' => count($permissions),
                'unique_permissions' => count($uniquePermissions),
                'duplicates_filtered' => $duplicateCount,
            ]);
        }

        $this->permissionGroups[] = [
            'module' => $module,
            'permissions' => $uniquePermissions,
            'defaultRoleAssignments' => $defaultRoleAssignments,
        ];
    }

    /**
     * Register multiple permission groups at once.
     *
     * @param  string  $module  Module name
     * @param  array<int, array{permissions: array<string>, defaultRoleAssignments?: array<string, array<string>>}>  $groups  Array of permission group definitions
     */
    public function registerMany(string $module, array $groups): void
    {
        foreach ($groups as $group) {
            $this->register(
                module: $module,
                permissions: $group['permissions'],
                defaultRoleAssignments: $group['defaultRoleAssignments'] ?? []
            );
        }
    }

    /**
     * Get all registered permission groups.
     *
     * @return array<int, array{module: string, permissions: array<string>, defaultRoleAssignments: array<string, array<string>>}>
     */
    public function getPermissions(): array
    {
        return $this->permissionGroups;
    }

    /**
     * Get permissions for a specific module.
     *
     * @param  string  $module  Module name
     * @return array<int, array{module: string, permissions: array<string>, defaultRoleAssignments: array<string, array<string>>}>
     */
    public function getPermissionsByModule(string $module): array
    {
        $module = strtolower($module);

        return array_filter($this->permissionGroups, fn ($group) => $group['module'] === $module);
    }

    /**
     * Seed all registered permissions into the database.
     *
     * This is automatically called by ModuleLifecycleService during module installation
     * and enabling. Only creates permissions that don't already exist.
     * Handles role assignments automatically.
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  bool  $strict  If true, any exception during seeding will cause the transaction to rollback.
     *                        If false (default), exceptions are logged but processing continues.
     */
    public function seed(bool $strict = false): void
    {
        if (empty($this->permissionGroups)) {
            return;
        }

        DB::transaction(function () use ($strict) {
            $totalCreated = 0;
            $totalFound = 0;
            $totalErrors = 0;

            foreach ($this->permissionGroups as $group) {
                $module = $group['module'];
                $permissions = $group['permissions'];
                $defaultRoleAssignments = $group['defaultRoleAssignments'];

                $createdPermissions = [];

                foreach ($permissions as $permission) {
                    $fullPermissionName = $this->buildPermissionName($module, $permission);

                    try {
                        $permissionModel = Permission::firstOrCreate(['name' => $fullPermissionName]);

                        if ($permissionModel->wasRecentlyCreated) {
                            $totalCreated++;
                        } else {
                            $totalFound++;
                        }

                        $createdPermissions[] = $permissionModel;
                    } catch (\Exception $e) {
                        $totalErrors++;
                        Log::error("PermissionRegistry: Failed to create permission: {$fullPermissionName}", [
                            'module' => $module,
                            'permission' => $permission,
                            'error' => $e->getMessage(),
                        ]);

                        if ($strict) {
                            throw $e;
                        }
                    }
                }

                if (! empty($defaultRoleAssignments) && ! empty($createdPermissions)) {
                    $this->assignToDefaultRoles($createdPermissions, $defaultRoleAssignments, $module);
                }
            }

            app(PermissionCacheService::class)->clear();

            $totalGroups = count($this->permissionGroups);
            if ($totalCreated > 0 || $totalFound > 0 || $totalErrors > 0 || $totalGroups > 0) {
                Log::debug('PermissionRegistry: Seeding completed', [
                    'created' => $totalCreated,
                    'found' => $totalFound,
                    'errors' => $totalErrors,
                    'total_groups' => $totalGroups,
                    'cache_cleared' => true,
                ]);
            }
        });
    }

    /**
     * Clean up permissions for a module when uninstalling.
     *
     * Removes all permissions that start with the module prefix (e.g., "blog.*", "newsletter.*")
     * and cleans up related pivot table entries.
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  string  $moduleName  The module name (e.g., 'Blog', 'Newsletter')
     * @return array{deleted: int, roles_cleaned: int, models_cleaned: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        $modulePrefix = strtolower($moduleName).'.';

        return DB::transaction(function () use ($modulePrefix, $moduleName) {
            try {
                Log::info("PermissionRegistry: Cleaning up permissions for '{$moduleName}'", [
                    'prefix' => $modulePrefix,
                ]);

                $permissions = Permission::where('name', 'like', $modulePrefix.'%')->get();

                if ($permissions->isEmpty()) {
                    Log::info("PermissionRegistry: No permissions found for '{$moduleName}'");
                    app(PermissionCacheService::class)->clear();

                    return [
                        'deleted' => 0,
                        'roles_cleaned' => 0,
                        'models_cleaned' => 0,
                    ];
                }

                $permissionIds = $permissions->pluck('id')->toArray();
                $permissionNames = $permissions->pluck('name')->toArray();

                Log::info('PermissionRegistry: Found permissions to remove', [
                    'count' => count($permissionIds),
                    'permissions' => $permissionNames,
                ]);

                $rolePermissionsDeleted = DB::table('role_has_permissions')
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();

                Log::info('PermissionRegistry: Removed permissions from roles', [
                    'count' => $rolePermissionsDeleted,
                ]);

                $modelPermissionsDeleted = DB::table('model_has_permissions')
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();

                Log::info('PermissionRegistry: Removed permissions from models', [
                    'count' => $modelPermissionsDeleted,
                ]);

                $permissionsDeleted = Permission::whereIn('id', $permissionIds)->delete();

                Log::info('PermissionRegistry: Deleted permissions', [
                    'count' => $permissionsDeleted,
                ]);

                app(PermissionCacheService::class)->clear();

                Log::info("PermissionRegistry: Permission cleanup completed for '{$moduleName}'");

                return [
                    'deleted' => $permissionsDeleted,
                    'roles_cleaned' => $rolePermissionsDeleted,
                    'models_cleaned' => $modelPermissionsDeleted,
                ];
            } catch (\Exception $e) {
                Log::error("PermissionRegistry: Failed to cleanup permissions for '{$moduleName}'", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        });
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

            $role = $this->roleRegistry->getRole($roleName);

            if (! $role) {
                try {
                    $role = Role::firstOrCreate(
                        ['name' => $roleName],
                        ['guard_name' => 'web']
                    );

                    if ($role->wasRecentlyCreated) {
                        Log::debug("PermissionRegistry: Created role during permission assignment: {$roleName}", [
                            'module' => $module,
                            'role_id' => $role->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("PermissionRegistry: Failed to create role during permission assignment: {$roleName}", [
                        'module' => $module,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
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
                    Log::error("PermissionRegistry: Failed to assign permissions to role: {$roleName}", [
                        'permissions' => $permissionsToAssign,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
