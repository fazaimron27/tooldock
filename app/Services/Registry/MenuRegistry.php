<?php

namespace App\Services\Registry;

use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Modules\Core\App\Models\Menu;

/**
 * Registry for managing application menu registration.
 *
 * Allows modules to register their menu items during service provider boot,
 * which are then automatically seeded into the database.
 *
 * Supports hierarchical menus with parent/child relationships.
 * Optimized for Redis with cache tags for efficient invalidation.
 */
class MenuRegistry
{
    /**
     * Cache tag name for menu-related cache entries.
     * Used for efficient bulk invalidation via Redis tags.
     */
    private const CACHE_TAG = 'menus';

    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Cache TTL (Time To Live) for menu cache entries.
     * Set to 24 hours as menus typically change infrequently.
     * Cache is automatically invalidated when menus are seeded/updated.
     *
     * Note: Since we use Redis tags for manual invalidation, we could use
     * rememberForever, but keeping TTL as a safety net for edge cases.
     */
    private const CACHE_TTL_HOURS = 24;

    /**
     * @var array<int, array{
     *     module: string,
     *     group: string,
     *     label: string,
     *     route: string,
     *     icon: string,
     *     order: int,
     *     permission: string|null,
     *     parent_key: string|null
     * }>
     */
    private array $menus = [];

    /**
     * Track registered menus by route to prevent duplicates.
     *
     * @var array<string, bool> Format: ['route' => true]
     */
    private array $registeredMenus = [];

    /**
     * Register a menu item.
     *
     * @param  string  $group  Menu group name
     * @param  string  $label  Menu item label
     * @param  string  $route  Route name
     * @param  string  $icon  Icon name
     * @param  int|null  $order  Display order
     * @param  string|null  $permission  Required permission to show this menu item
     * @param  string|null  $parentKey  Parent menu route (for hierarchical menus)
     * @param  string|null  $module  Module name (auto-detected if not provided)
     */
    public function registerItem(
        string $group,
        string $label,
        string $route,
        string $icon,
        ?int $order = null,
        ?string $permission = null,
        ?string $parentKey = null,
        ?string $module = null
    ): void {
        if (isset($this->registeredMenus[$route])) {
            Log::warning('MenuRegistry: Duplicate menu registration', [
                'route' => $route,
                'label' => $label,
            ]);

            return;
        }

        $this->registeredMenus[$route] = true;

        $order = $order ?? count($this->menus) * 10;

        $this->menus[] = [
            'module' => $module ? strtolower($module) : null,
            'group' => $group,
            'label' => $label,
            'route' => $route,
            'icon' => $icon,
            'order' => $order,
            'permission' => $permission,
            'parent_key' => $parentKey,
        ];
    }

    /**
     * Register a menu item (alias for registerItem for consistency with other registries).
     *
     * @param  string  $group  Menu group name
     * @param  string  $label  Menu item label
     * @param  string  $route  Route name
     * @param  string  $icon  Icon name
     * @param  int|null  $order  Display order
     * @param  string|null  $permission  Required permission to show this menu item
     * @param  string|null  $parentKey  Parent menu route (for hierarchical menus)
     * @param  string|null  $module  Module name (auto-detected if not provided)
     */
    public function register(
        string $group,
        string $label,
        string $route,
        string $icon,
        ?int $order = null,
        ?string $permission = null,
        ?string $parentKey = null,
        ?string $module = null
    ): void {
        $this->registerItem($group, $label, $route, $icon, $order, $permission, $parentKey, $module);
    }

    /**
     * Get all registered menus.
     *
     * @return array<int, array{module: string|null, group: string, label: string, route: string, icon: string, order: int, permission: string|null, parent_key: string|null}>
     */
    public function getRegisteredMenus(): array
    {
        return $this->menus;
    }

    /**
     * Seed all registered menus into the database.
     *
     * This is automatically called by ModuleLifecycleService during module installation
     * and enabling. Only creates menus that don't already exist (based on route uniqueness).
     * Handles parent/child relationships automatically.
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  bool  $strict  If true, any exception during seeding will cause the transaction to rollback.
     *                        If false (default), exceptions are logged but processing continues.
     */
    public function seed(bool $strict = false): void
    {
        if (empty($this->menus)) {
            return;
        }

        DB::transaction(function () use ($strict) {
            $existingMenus = Menu::all()->keyBy('route');
            $parentMap = [];
            $created = 0;
            $found = 0;
            $errors = 0;

            foreach ($this->menus as $menu) {
                if (empty($menu['parent_key'])) {
                    if ($existingMenus->has($menu['route'])) {
                        $parentMap[$menu['route']] = $existingMenus->get($menu['route']);
                        $found++;

                        continue;
                    }

                    try {
                        $menuModel = Menu::create([
                            'group' => $menu['group'],
                            'label' => $menu['label'],
                            'route' => $menu['route'],
                            'icon' => $menu['icon'],
                            'order' => $menu['order'],
                            'permission' => $menu['permission'],
                            'module' => $menu['module'],
                            'is_active' => true,
                        ]);

                        $parentMap[$menu['route']] = $menuModel;
                        $existingMenus->put($menu['route'], $menuModel);
                        $created++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('MenuRegistry: Failed to create menu', [
                            'module' => $menu['module'],
                            'route' => $menu['route'],
                            'label' => $menu['label'],
                            'error' => $e->getMessage(),
                        ]);

                        if ($strict) {
                            throw $e;
                        }
                    }
                }
            }

            $maxDepth = 10;
            $depth = 0;

            while ($depth < $maxDepth) {
                $createdInThisPass = false;

                foreach ($this->menus as $menu) {
                    if (! empty($menu['parent_key'])) {
                        if ($existingMenus->has($menu['route']) || isset($parentMap[$menu['route']])) {
                            $found++;

                            continue;
                        }

                        $parent = $parentMap[$menu['parent_key']] ?? null;

                        if (! $parent) {
                            continue;
                        }

                        try {
                            $childMenu = Menu::create([
                                'parent_id' => $parent->id,
                                'group' => $menu['group'],
                                'label' => $menu['label'],
                                'route' => $menu['route'],
                                'icon' => $menu['icon'],
                                'order' => $menu['order'],
                                'permission' => $menu['permission'],
                                'module' => $menu['module'],
                                'is_active' => true,
                            ]);

                            $parentMap[$menu['route']] = $childMenu;
                            $existingMenus->put($menu['route'], $childMenu);
                            $created++;
                            $createdInThisPass = true;
                        } catch (\Exception $e) {
                            $errors++;
                            Log::error('MenuRegistry: Failed to create child menu', [
                                'module' => $menu['module'],
                                'route' => $menu['route'],
                                'label' => $menu['label'],
                                'parent_key' => $menu['parent_key'],
                                'error' => $e->getMessage(),
                            ]);

                            if ($strict) {
                                throw $e;
                            }
                        }
                    }
                }

                if (! $createdInThisPass) {
                    break;
                }

                $depth++;
            }

            if ($depth >= $maxDepth) {
                Log::warning('MenuRegistry: Maximum depth reached while seeding menus');
            }

            if ($created > 0 || $found > 0 || $errors > 0) {
                Log::debug('MenuRegistry: Seeding completed', [
                    'created' => $created,
                    'found' => $found,
                    'errors' => $errors,
                    'total' => count($this->menus),
                ]);

                $this->clearCache();
            }
        });
    }

    /**
     * Clean up menus for a module when uninstalling.
     *
     * Removes all menus that belong to the specified module.
     * Only deletes children that also belong to the same module.
     * Detects and warns about orphaned menus (children from other modules).
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  string  $moduleName  The module name (e.g., 'Blog', 'Newsletter')
     * @return array{deleted: int, orphaned: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        $moduleName = strtolower($moduleName);

        return DB::transaction(function () use ($moduleName) {
            $menus = Menu::where('module', $moduleName)->get();

            if ($menus->isEmpty()) {
                Log::info("MenuRegistry: No menus found for module '{$moduleName}'");

                return [
                    'deleted' => 0,
                    'orphaned' => 0,
                ];
            }

            $menuIds = $menus->pluck('id')->toArray();
            $menuRoutes = $menus->pluck('route')->toArray();

            $orphanedCount = $this->detectOrphanedMenus($menuIds, $moduleName);
            $totalToDelete = $this->countMenuTree($menuIds, $moduleName);
            $this->deleteMenuTree($menuIds, $moduleName);

            Log::info("MenuRegistry: Cleaned up menus for module '{$moduleName}'", [
                'count' => count($menuIds),
                'total_deleted' => $totalToDelete,
                'orphaned' => $orphanedCount,
                'routes' => $menuRoutes,
            ]);

            $this->clearCache();

            return [
                'deleted' => $totalToDelete,
                'orphaned' => $orphanedCount,
            ];
        });
    }

    /**
     * Get all registered menus grouped and sorted.
     *
     * Reads from cache/database instead of memory array.
     * Filters by user permissions and builds hierarchical structure.
     *
     * Optimized for Redis with cache tags for efficient invalidation.
     * Cache TTL is set to 24 hours, but cache is automatically invalidated
     * when menus are modified via seed() or cleanup() methods.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user  User to check permissions against
     * @return array<string, array<int, array{label: string, route: string, icon: string, order: int, permission?: string, children?: array}>>
     */
    public function getMenus(?\Illuminate\Contracts\Auth\Authenticatable $user = null): array
    {
        $userId = $user?->id ?? '';
        $cacheKey = "menus:user:{$userId}";

        return $this->cacheService->remember(
            $cacheKey,
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => $this->loadMenusFromDatabase($user),
            self::CACHE_TAG
        );
    }

    /**
     * Load menus from database and build hierarchical structure.
     *
     * Optimized for Redis: Loads all menus (including nested children) in minimal queries
     * to reduce database load and improve cache efficiency.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user  User to check permissions against
     * @return array<string, array<int, array{label: string, route: string, icon: string, order: int, permission?: string, children?: array}>>
     */
    private function loadMenusFromDatabase(?\Illuminate\Contracts\Auth\Authenticatable $user = null): array
    {
        $allMenus = Menu::active()
            ->orderBy('group')
            ->orderBy('order')
            ->get()
            ->keyBy('id');

        $rootMenus = $allMenus->filter(fn ($menu) => $menu->parent_id === null);
        $childrenMap = $allMenus->groupBy('parent_id');

        foreach ($allMenus as $menu) {
            $menu->setRelation('children', $childrenMap->get($menu->id, collect())
                ->sortBy('order')
                ->values());
        }

        $sorted = [];
        $groupOrder = ['Main' => 0, 'Dashboard' => 1];

        foreach ($rootMenus as $menu) {
            $group = $menu->group;

            if (! isset($sorted[$group])) {
                $sorted[$group] = [];
            }

            $item = $this->buildMenuItem($menu, $user);

            if ($item !== null) {
                $sorted[$group][] = $item;
            }
        }

        foreach ($sorted as $group => $items) {
            usort($sorted[$group], fn ($a, $b) => $a['order'] <=> $b['order']);
        }

        $sorted = array_filter($sorted, fn ($items) => ! empty($items));

        uksort($sorted, function ($groupA, $groupB) use ($groupOrder) {
            $orderA = $groupOrder[$groupA] ?? 999;
            $orderB = $groupOrder[$groupB] ?? 999;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcasecmp($groupA, $groupB);
        });

        return $sorted;
    }

    /**
     * Build a menu item with children, filtering by permissions.
     *
     * @param  Menu  $menu  The menu model
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user  User to check permissions against
     * @return array{label: string, route: string, icon: string, order: int, permission?: string, children?: array}|null
     */
    private function buildMenuItem(Menu $menu, ?\Illuminate\Contracts\Auth\Authenticatable $user): ?array
    {
        if (! empty($menu->permission)) {
            if (! $user) {
                return null;
            }

            if (! Gate::forUser($user)->allows($menu->permission)) {
                return null;
            }
        }

        $item = [
            'label' => $menu->label,
            'route' => $menu->route,
            'icon' => $menu->icon,
            'order' => $menu->order,
        ];

        if (! empty($menu->permission)) {
            $item['permission'] = $menu->permission;
        }

        if ($menu->children->isNotEmpty()) {
            $children = [];
            foreach ($menu->children->sortBy('order') as $child) {
                $childItem = $this->buildMenuItem($child, $user);
                if ($childItem !== null) {
                    $children[] = $childItem;
                }
            }

            if (! empty($children)) {
                $item['children'] = $children;
            }
        }

        return $item;
    }

    /**
     * Recursively delete a menu tree, only deleting children from the same module.
     *
     * @param  array<string>  $menuIds  Array of menu IDs to delete
     * @param  string  $moduleName  Module name to filter children by
     */
    private function deleteMenuTree(array $menuIds, string $moduleName): void
    {
        if (empty($menuIds)) {
            return;
        }

        $childMenus = Menu::whereIn('parent_id', $menuIds)
            ->where('module', $moduleName)
            ->pluck('id')
            ->toArray();

        if (! empty($childMenus)) {
            $this->deleteMenuTree($childMenus, $moduleName);
        }

        Menu::whereIn('id', $menuIds)->delete();
    }

    /**
     * Detect and warn about orphaned menus (children from other modules).
     *
     * When a module's menu is deleted, any children from other modules
     * will become orphaned (parent_id set to null by foreign key constraint).
     *
     * @param  array<string>  $menuIds  Array of menu IDs being deleted
     * @param  string  $moduleName  Module name being uninstalled
     * @return int Number of orphaned menus detected
     */
    private function detectOrphanedMenus(array $menuIds, string $moduleName): int
    {
        if (empty($menuIds)) {
            return 0;
        }

        $orphanedMenus = Menu::whereIn('parent_id', $menuIds)
            ->where('module', '!=', $moduleName)
            ->whereNotNull('module')
            ->get();

        if ($orphanedMenus->isNotEmpty()) {
            Log::warning('MenuRegistry: Orphaned menus detected', [
                'uninstalling_module' => $moduleName,
                'orphaned_count' => $orphanedMenus->count(),
                'orphaned_menus' => $orphanedMenus->map(fn ($menu) => [
                    'id' => $menu->id,
                    'label' => $menu->label,
                    'route' => $menu->route,
                    'group' => $menu->group,
                    'module' => $menu->module,
                    'parent_id' => $menu->parent_id,
                ])->toArray(),
            ]);
        }

        return $orphanedMenus->count();
    }

    /**
     * Count total menus in a tree (including children) that belong to the same module.
     *
     * @param  array<string>  $menuIds  Array of menu IDs to count
     * @param  string  $moduleName  Module name to filter children by
     * @return int Total count including all children
     */
    private function countMenuTree(array $menuIds, string $moduleName): int
    {
        if (empty($menuIds)) {
            return 0;
        }

        $count = count($menuIds);

        $childMenus = Menu::whereIn('parent_id', $menuIds)
            ->where('module', $moduleName)
            ->pluck('id')
            ->toArray();

        if (! empty($childMenus)) {
            $count += $this->countMenuTree($childMenus, $moduleName);
        }

        return $count;
    }

    /**
     * Clear all menu caches.
     *
     * Optimized for Redis - uses tag-based flush for efficient invalidation.
     * This method is called automatically when menus are seeded or cleaned up,
     * ensuring cache consistency without manual intervention.
     */
    public function clearCache(): void
    {
        $this->cacheService->clearTag(self::CACHE_TAG, 'MenuRegistry');
    }

    /**
     * Clear menu cache for a specific user.
     *
     * Called when user roles, permissions, or groups are modified
     * to ensure menu visibility updates immediately.
     *
     * @param  string  $userId  User ID
     */
    public function clearCacheForUser(string $userId): void
    {
        $cacheKey = "menus:user:{$userId}";
        $this->cacheService->forget($cacheKey, self::CACHE_TAG);
    }
}
