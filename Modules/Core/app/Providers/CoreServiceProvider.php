<?php

namespace Modules\Core\Providers;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\RoleRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\Menu;
use Modules\Core\App\Models\User;
use Modules\Core\App\Observers\MenuObserver;
use Modules\Core\App\Observers\PermissionObserver;
use Modules\Core\App\Observers\RoleObserver;
use Modules\Core\App\Observers\UserObserver;
use Modules\Core\App\Services\SuperAdminService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Core';

    protected string $nameLower = 'core';

    /**
     * Boot the application events.
     */
    public function boot(
        MenuRegistry $menuRegistry,
        PermissionRegistry $permissionRegistry,
        RoleRegistry $roleRegistry,
        SuperAdminService $superAdminService,
        DashboardWidgetRegistry $widgetRegistry
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        // Register Core module dashboard as child of Dashboard
        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Core Dashboard',
            route: 'core.dashboard',
            icon: 'LayoutDashboard',
            order: 10,
            permission: 'core.dashboard.view',
            parentKey: 'dashboard',
            module: $this->name
        );

        $menuRegistry->registerItem(
            group: 'System',
            label: 'User Management',
            route: 'core.user-management',
            icon: 'Users',
            order: 10,
            permission: null,
            parentKey: null,
            module: $this->name
        );

        $menuRegistry->registerItem(
            group: 'System',
            label: 'Users',
            route: 'core.users.index',
            icon: 'Users',
            order: 10,
            permission: 'core.users.view',
            parentKey: 'core.user-management',
            module: $this->name
        );

        $menuRegistry->registerItem(
            group: 'System',
            label: 'Roles',
            route: 'core.roles.index',
            icon: 'Shield',
            order: 20,
            permission: 'core.roles.manage',
            parentKey: 'core.user-management',
            module: $this->name
        );

        $menuRegistry->registerItem(
            group: 'System',
            label: 'Modules',
            route: 'core.modules.index',
            icon: 'Package',
            order: 100,
            permission: 'core.modules.manage',
            parentKey: null,
            module: $this->name
        );

        $this->registerDefaultRoles($roleRegistry);
        $this->registerDefaultPermissions($permissionRegistry);

        $superAdminService->ensureExists($roleRegistry);

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Users',
                value: fn () => User::count(),
                icon: 'Users',
                module: $this->name,
                group: 'User Management',
                order: 10,
                scope: 'overview'
            )
        );

        // Stat Widget: Total Roles (Detail - shown on module dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Roles',
                value: fn () => Role::count(),
                icon: 'Shield',
                module: $this->name,
                group: 'User Management',
                order: 11,
                scope: 'detail'
            )
        );

        // Stat Widget: Total Permissions (Detail - shown on module dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Permissions',
                value: fn () => Permission::count(),
                icon: 'Key',
                module: $this->name,
                group: 'User Management',
                order: 12,
                scope: 'detail'
            )
        );

        // Chart Widget: User Growth Over Time (Detail - shown on module dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'User Growth',
                value: 0,
                icon: 'TrendingUp',
                module: $this->name,
                group: 'Analytics',
                description: 'New user registrations over the last 6 months',
                chartType: 'line',
                data: fn () => $this->getUserGrowthData(),
                order: 13,
                scope: 'detail'
            )
        );

        // Activity Widget: Recent User Registrations (Detail - shown on module dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Users',
                value: 0,
                icon: 'UserPlus',
                module: $this->name,
                group: 'Activity',
                description: 'Latest user registrations',
                data: fn () => $this->getRecentUsersActivity(),
                order: 14,
                scope: 'detail'
            )
        );

        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'hasRole')) {
                return $user->hasRole(Roles::SUPER_ADMIN) ? true : null;
            }

            return null;
        });

        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        Menu::observe(MenuObserver::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void {}

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void {}

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    if ($config === 'permission.php') {
                        $key = 'permission';
                    }

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    /**
     * Register default roles for the Core module.
     */
    private function registerDefaultRoles(RoleRegistry $registry): void
    {
        $registry->register('core', Roles::SUPER_ADMIN);
        $registry->register('core', Roles::ADMINISTRATOR);
        $registry->register('core', Roles::MANAGER);
        $registry->register('core', Roles::STAFF);
        $registry->register('core', Roles::AUDITOR);
    }

    /**
     * Register default permissions for the Core module.
     */
    private function registerDefaultPermissions(PermissionRegistry $registry): void
    {
        $registry->register('core', [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.manage',
            'modules.manage',
        ], [
            Roles::ADMINISTRATOR => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'roles.manage',
                'modules.manage',
            ],
            Roles::MANAGER => [
                'dashboard.view',
                'users.view',
            ],
            Roles::STAFF => [
                'dashboard.view',
            ],
            Roles::AUDITOR => [
                'dashboard.view',
                'users.view',
            ],
        ]);
    }

    /**
     * Get user growth data for chart widget.
     *
     * Optimized: Uses a single query with GROUP BY instead of 6 separate queries.
     */
    private function getUserGrowthData(): array
    {
        $now = now();
        $startDate = $now->copy()->subMonths(5)->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        // Single query to get counts grouped by month
        $results = User::selectRaw('
                DATE_TRUNC(\'month\', created_at)::date as month,
                COUNT(*) as count
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy(function ($item) {
                // Extract Y-m from date string (format: YYYY-MM-DD)
                return substr($item->month, 0, 7);
            })
            ->map(fn ($item) => (int) $item->count)
            ->toArray();

        // Build months array with data from query
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthKey = $month->format('Y-m');

            $months[] = [
                'name' => $month->format('M Y'),
                'value' => $results[$monthKey] ?? 0,
            ];
        }

        return $months;
    }

    /**
     * Get recent users activity for activity widget.
     */
    private function getRecentUsersActivity(): array
    {
        return User::latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'title' => "New user registered: {$user->name}",
                    'timestamp' => $user->created_at->diffForHumans(),
                    'icon' => 'UserPlus',
                    'iconColor' => 'bg-blue-500',
                ];
            })
            ->toArray();
    }
}
