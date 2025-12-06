<?php

namespace App\Services\Registry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\Role;

/**
 * Registry for managing default role registration.
 *
 * Allows modules to register their default roles during service provider boot.
 * Roles are automatically seeded into the database during module installation
 * and enabling via ModuleLifecycleService, similar to how permissions and categories are synced.
 *
 * Provides a standardized way for modules to register their roles
 * with automatic guard name handling.
 */
class RoleRegistry
{
    /**
     * @var array<int, array{
     *     module: string,
     *     name: string,
     *     guard_name: string
     * }>
     */
    private array $roles = [];

    /**
     * Track registered roles by module and guard to prevent duplicates.
     *
     * @var array<string, array<string, bool>> Format: ['module' => ['name:guard' => true]]
     */
    private array $registeredRoles = [];

    /**
     * Register a default role for a module.
     *
     * @param  string  $module  Module name (e.g., 'Core', 'Blog')
     * @param  string  $name  Role name (e.g., 'Administrator', 'Editor')
     * @param  string  $guardName  Guard name (defaults to 'web')
     */
    public function register(string $module, string $name, string $guardName = 'web'): void
    {
        $module = strtolower($module);
        $key = "{$name}:{$guardName}";

        if (isset($this->registeredRoles[$module][$key])) {
            Log::warning('RoleRegistry: Duplicate role registration', [
                'module' => $module,
                'name' => $name,
                'guard' => $guardName,
            ]);

            return;
        }

        if (! isset($this->registeredRoles[$module])) {
            $this->registeredRoles[$module] = [];
        }
        $this->registeredRoles[$module][$key] = true;

        $this->roles[] = [
            'module' => $module,
            'name' => $name,
            'guard_name' => $guardName,
        ];
    }

    /**
     * Register multiple roles at once.
     *
     * @param  string  $module  Module name
     * @param  array<int, array{name: string, guard_name?: string}>  $roles  Array of role definitions
     */
    public function registerMany(string $module, array $roles): void
    {
        foreach ($roles as $role) {
            $this->register(
                module: $module,
                name: $role['name'],
                guardName: $role['guard_name'] ?? 'web'
            );
        }
    }

    /**
     * Get all registered roles.
     *
     * @return array<int, array{module: string, name: string, guard_name: string}>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Get roles for a specific module.
     *
     * @param  string  $module  Module name
     * @return array<int, array{module: string, name: string, guard_name: string}>
     */
    public function getRolesByModule(string $module): array
    {
        $module = strtolower($module);

        return array_filter($this->roles, fn ($role) => $role['module'] === $module);
    }

    /**
     * Get a role by name and guard.
     *
     * @param  string  $name  Role name
     * @param  string  $guardName  Guard name (defaults to 'web')
     * @return Role|null The role model or null if not found
     */
    public function getRole(string $name, string $guardName = 'web'): ?Role
    {
        return Role::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    /**
     * Seed all registered roles into the database.
     *
     * This is automatically called by ModuleLifecycleService during module installation
     * and enabling. Only creates roles that don't already exist (based on name + guard uniqueness).
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  bool  $strict  If true, any exception during seeding will cause the transaction to rollback.
     *                        If false (default), exceptions are logged but processing continues.
     */
    public function seed(bool $strict = false): void
    {
        if (empty($this->roles)) {
            return;
        }

        DB::transaction(function () use ($strict) {
            $created = 0;
            $found = 0;
            $errors = 0;

            foreach ($this->roles as $roleData) {
                $name = $roleData['name'];
                $guardName = $roleData['guard_name'];
                $module = $roleData['module'];

                try {
                    $role = Role::firstOrCreate(
                        [
                            'name' => $name,
                            'guard_name' => $guardName,
                        ]
                    );

                    if ($role->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $found++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("RoleRegistry: Failed to create role: {$name}", [
                        'module' => $module,
                        'name' => $name,
                        'guard' => $guardName,
                        'error' => $e->getMessage(),
                    ]);

                    if ($strict) {
                        throw $e;
                    }
                }
            }

            if ($created > 0 || $found > 0 || $errors > 0) {
                Log::debug('RoleRegistry: Seeding completed', [
                    'created' => $created,
                    'found' => $found,
                    'errors' => $errors,
                    'total' => count($this->roles),
                ]);
            }
        });
    }

    /**
     * Clean up roles for a module when uninstalling.
     *
     * Removes all roles that belong to the specified module.
     * Note: This only removes roles if they are not in use (no users assigned).
     * Protected roles (like Super Admin) should not be removed.
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  string  $moduleName  The module name (e.g., 'Blog', 'Newsletter')
     * @return array{deleted: int, skipped: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        $moduleName = strtolower($moduleName);

        return DB::transaction(function () use ($moduleName) {
            $moduleRoles = $this->getRolesByModule($moduleName);

            if (empty($moduleRoles)) {
                Log::info("RoleRegistry: No roles found for module '{$moduleName}'");

                return [
                    'deleted' => 0,
                    'skipped' => 0,
                ];
            }

            $deleted = 0;
            $skipped = 0;

            $protectedRoles = [Roles::SUPER_ADMIN];

            foreach ($moduleRoles as $roleData) {
                $role = $this->getRole($roleData['name'], $roleData['guard_name']);

                if (! $role) {
                    continue;
                }

                if (in_array($roleData['name'], $protectedRoles, true)) {
                    Log::info("RoleRegistry: Skipping protected role deletion: {$roleData['name']}", [
                        'module' => $moduleName,
                        'role' => $roleData['name'],
                    ]);
                    $skipped++;

                    continue;
                }

                $hasUsers = $role->users()->exists();

                if ($hasUsers) {
                    Log::info("RoleRegistry: Skipping role deletion (has users assigned): {$roleData['name']}", [
                        'module' => $moduleName,
                        'role' => $roleData['name'],
                    ]);
                    $skipped++;

                    continue;
                }

                try {
                    $role->permissions()->detach();
                    $role->delete();
                    $deleted++;

                    Log::info("RoleRegistry: Deleted role: {$roleData['name']}", [
                        'module' => $moduleName,
                        'role' => $roleData['name'],
                    ]);
                } catch (\Exception $e) {
                    Log::error("RoleRegistry: Failed to delete role: {$roleData['name']}", [
                        'module' => $moduleName,
                        'role' => $roleData['name'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("RoleRegistry: Cleanup completed for module '{$moduleName}'", [
                'deleted' => $deleted,
                'skipped' => $skipped,
            ]);

            return [
                'deleted' => $deleted,
                'skipped' => $skipped,
            ];
        });
    }
}
