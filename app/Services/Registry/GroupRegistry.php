<?php

namespace App\Services\Registry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Groups\Models\Group;

/**
 * Registry for managing group registration.
 *
 * Allows modules to register their default groups during service provider boot.
 * Groups are automatically seeded into the database during module installation
 * and enabling via ModuleLifecycleService, similar to how roles and permissions are synced.
 *
 * Provides a standardized way for modules to register their groups
 * with automatic slug generation.
 */
class GroupRegistry
{
    /**
     * @var array<int, array{
     *     module: string,
     *     name: string,
     *     slug: string,
     *     description: string|null
     * }>
     */
    private array $groups = [];

    /**
     * Track registered groups by module to prevent duplicates.
     *
     * @var array<string, array<string, bool>> Format: ['module' => ['name' => true]]
     */
    private array $registeredGroups = [];

    /**
     * Register a group for a module.
     *
     * @param  string  $module  Module name (e.g., 'Groups', 'Blog')
     * @param  string  $name  Group name (e.g., 'Guest', 'Editors')
     * @param  string|null  $description  Optional group description
     * @param  string|null  $slug  Optional slug (auto-generated from name if not provided)
     */
    public function register(string $module, string $name, ?string $description = null, ?string $slug = null): void
    {
        $module = strtolower($module);

        if (isset($this->registeredGroups[$module][$name])) {
            Log::warning('GroupRegistry: Duplicate group registration', [
                'module' => $module,
                'name' => $name,
            ]);

            return;
        }

        if (! isset($this->registeredGroups[$module])) {
            $this->registeredGroups[$module] = [];
        }
        $this->registeredGroups[$module][$name] = true;

        $this->groups[] = [
            'module' => $module,
            'name' => $name,
            'slug' => $slug ?? Str::slug($name),
            'description' => $description,
        ];
    }

    /**
     * Register multiple groups at once.
     *
     * @param  string  $module  Module name
     * @param  array<int, array{name: string, description?: string, slug?: string}>  $groups  Array of group definitions
     */
    public function registerMany(string $module, array $groups): void
    {
        foreach ($groups as $group) {
            $this->register(
                module: $module,
                name: $group['name'],
                description: $group['description'] ?? null,
                slug: $group['slug'] ?? null
            );
        }
    }

    /**
     * Get all registered groups.
     *
     * @return array<int, array{module: string, name: string, slug: string, description: string|null}>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Get groups for a specific module.
     *
     * @param  string  $module  Module name
     * @return array<int, array{module: string, name: string, slug: string, description: string|null}>
     */
    public function getGroupsByModule(string $module): array
    {
        $module = strtolower($module);

        return array_filter($this->groups, fn ($group) => $group['module'] === $module);
    }

    /**
     * Get a group by name.
     *
     * @param  string  $name  Group name
     * @return Group|null The group model or null if not found
     */
    public function getGroup(string $name): ?Group
    {
        return Group::where('name', $name)->first();
    }

    /**
     * Seed all registered groups into the database.
     *
     * This is automatically called by ModuleLifecycleService during module installation
     * and enabling. Only creates groups that don't already exist (based on name uniqueness).
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  bool  $strict  If true, any exception during seeding will cause the transaction to rollback.
     *                        If false (default), exceptions are logged but processing continues.
     */
    public function seed(bool $strict = false): void
    {
        if (empty($this->groups)) {
            return;
        }

        DB::transaction(function () use ($strict) {
            $created = 0;
            $found = 0;
            $errors = 0;

            foreach ($this->groups as $groupData) {
                $name = $groupData['name'];
                $slug = $groupData['slug'];
                $description = $groupData['description'];
                $module = $groupData['module'];

                try {
                    $group = Group::firstOrCreate(
                        ['name' => $name],
                        [
                            'slug' => $slug,
                            'description' => $description,
                        ]
                    );

                    if ($group->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $found++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("GroupRegistry: Failed to create group: {$name}", [
                        'module' => $module,
                        'name' => $name,
                        'slug' => $slug,
                        'error' => $e->getMessage(),
                    ]);

                    if ($strict) {
                        throw $e;
                    }
                }
            }

            if ($created > 0 || $found > 0 || $errors > 0) {
                Log::debug('GroupRegistry: Seeding completed', [
                    'created' => $created,
                    'found' => $found,
                    'errors' => $errors,
                    'total' => count($this->groups),
                ]);
            }
        });
    }

    /**
     * Clean up groups for a module when uninstalling.
     *
     * Removes all groups that belong to the specified module.
     * Note: This only removes groups if they are not in use (no users assigned).
     * Protected groups (like Guest) should not be removed.
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  string  $moduleName  The module name (e.g., 'Groups', 'Blog')
     * @return array{deleted: int, skipped: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        $moduleName = strtolower($moduleName);

        return DB::transaction(function () use ($moduleName) {
            $moduleGroups = $this->getGroupsByModule($moduleName);

            if (empty($moduleGroups)) {
                Log::info("GroupRegistry: No groups found for module '{$moduleName}'");

                return [
                    'deleted' => 0,
                    'skipped' => 0,
                ];
            }

            $deleted = 0;
            $skipped = 0;

            $protectedGroups = ['Guest'];

            foreach ($moduleGroups as $groupData) {
                $group = $this->getGroup($groupData['name']);

                if (! $group) {
                    continue;
                }

                if (in_array($groupData['name'], $protectedGroups, true)) {
                    Log::info("GroupRegistry: Skipping protected group deletion: {$groupData['name']}", [
                        'module' => $moduleName,
                        'name' => $groupData['name'],
                    ]);
                    $skipped++;

                    continue;
                }

                $hasUsers = $group->users()->exists();

                if ($hasUsers) {
                    Log::info("GroupRegistry: Skipping group deletion (has users assigned): {$groupData['name']}", [
                        'module' => $moduleName,
                        'name' => $groupData['name'],
                    ]);
                    $skipped++;

                    continue;
                }

                try {
                    $group->permissions()->detach();
                    $group->users()->detach();
                    $group->delete();
                    $deleted++;

                    Log::info("GroupRegistry: Deleted group: {$groupData['name']}", [
                        'module' => $moduleName,
                        'name' => $groupData['name'],
                    ]);
                } catch (\Exception $e) {
                    Log::error("GroupRegistry: Failed to delete group: {$groupData['name']}", [
                        'module' => $moduleName,
                        'name' => $groupData['name'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("GroupRegistry: Cleanup completed for module '{$moduleName}'", [
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
