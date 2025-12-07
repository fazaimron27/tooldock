<?php

namespace Modules\Groups\Providers;

use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\GroupRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\Role;
use Modules\Groups\Models\Group;
use Modules\Groups\Observers\GroupObserver;
use Modules\Groups\Services\GroupsDashboardService;
use Modules\Groups\Services\GroupsGroupRegistrar;
use Modules\Groups\Services\GroupsMenuRegistrar;
use Modules\Groups\Services\GroupsPermissionRegistrar;
use Modules\Groups\Services\GroupsRoleService;
use Modules\Groups\Services\GroupsSettingsRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class GroupsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Groups';

    protected string $nameLower = 'groups';

    /**
     * Boot the application events.
     */
    public function boot(
        MenuRegistry $menuRegistry,
        SettingsRegistry $settingsRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        PermissionRegistry $permissionRegistry,
        GroupRegistry $groupRegistry,
        GroupsMenuRegistrar $menuRegistrar,
        GroupsSettingsRegistrar $settingsRegistrar,
        GroupsDashboardService $dashboardService,
        GroupsPermissionRegistrar $permissionRegistrar,
        GroupsGroupRegistrar $groupRegistrar
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $menuRegistrar->register($menuRegistry, $this->name);
        $settingsRegistrar->register($settingsRegistry, $this->name);
        $dashboardService->registerWidgets($widgetRegistry, $this->name);
        $permissionRegistrar->registerPermissions($permissionRegistry);
        $groupRegistrar->register($groupRegistry, $this->name);
        $this->registerAuthorization();
        $this->registerObservers();
        $this->registerRoleGroupsRelationship();
        $this->attachRolesToGroups();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

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
     * Register authorization gates.
     *
     * This Gate::before callback checks if a user has a permission through
     * any of their groups. If found, it returns true. Otherwise, it returns
     * null to allow Spatie/standard gates to continue their check.
     *
     * Skips checking for unauthenticated users and certain system abilities
     * to prevent redirect loops.
     */
    private function registerAuthorization(): void
    {
        Gate::before(function ($user, $ability) {
            if (! $user) {
                return null;
            }

            if (! method_exists($user, 'hasGroupPermission')) {
                return null;
            }

            $skipAbilities = ['login', 'register', 'logout', 'password.request', 'password.reset'];
            if (in_array($ability, $skipAbilities, true)) {
                return null;
            }

            return $user->hasGroupPermission($ability) ? true : null;
        });
    }

    /**
     * Register model observers.
     */
    private function registerObservers(): void
    {
        Group::observe(GroupObserver::class);
    }

    /**
     * Register relationship macro for Role model to access groups.
     *
     * This allows Role models to access their associated groups through
     * the groups_roles pivot table, enabling efficient eager loading.
     */
    private function registerRoleGroupsRelationship(): void
    {
        if (! Role::hasMacro('groups')) {
            Role::macro('groups', function (): BelongsToMany {
                /** @var Role $this */
                return $this->belongsToMany(Group::class, 'groups_roles')
                    ->withTimestamps();
            });
        }
    }

    /**
     * Attach roles to groups after all service providers have booted.
     *
     * This ensures roles and groups are seeded before attaching relationships.
     */
    private function attachRolesToGroups(): void
    {
        $this->app->booted(function () {
            $roleService = app(GroupsRoleService::class);
            $groupRegistrar = app(GroupsGroupRegistrar::class);
            $groupRegistrar->attachRolesToGroups($roleService);
        });
    }
}
