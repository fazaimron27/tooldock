<?php

namespace Modules\Core\Providers;

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
use Modules\Core\App\Services\CoreDashboardService;
use Modules\Core\App\Services\CoreMenuRegistrar;
use Modules\Core\App\Services\CorePermissionRegistrar;
use Modules\Core\App\Services\SuperAdminService;
use Modules\Core\Console\BulkCreateUsersCommand;
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
        DashboardWidgetRegistry $widgetRegistry,
        CoreMenuRegistrar $menuRegistrar,
        CoreDashboardService $dashboardService,
        CorePermissionRegistrar $permissionRegistrar
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $menuRegistrar->register($menuRegistry, $this->name);
        $permissionRegistrar->registerRoles($roleRegistry);
        $permissionRegistrar->registerPermissions($permissionRegistry);
        $superAdminService->ensureExists($roleRegistry);
        $dashboardService->registerWidgets($widgetRegistry, $this->name);
        $this->registerAuthorization();
        $this->registerObservers();
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
    protected function registerCommands(): void
    {
        $this->commands([
            BulkCreateUsersCommand::class,
        ]);
    }

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
     * Register authorization gates and policies.
     */
    private function registerAuthorization(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'hasRole')) {
                return $user->hasRole(Roles::SUPER_ADMIN) ? true : null;
            }

            return null;
        });
    }

    /**
     * Register model observers.
     */
    private function registerObservers(): void
    {
        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        Menu::observe(MenuObserver::class);
    }
}
