<?php

/**
 * Core Service Provider.
 *
 * Main service provider for the Core module, handling
 * registration of config, views, migrations, and services.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Providers;

use App\Services\Core\UserPreferenceService;
use App\Services\Registry\CommandRegistry;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\InertiaSharedDataRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\RoleRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Console\BulkCreateUsersCommand;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\Menu;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Core\Observers\MenuObserver;
use Modules\Core\Observers\PermissionObserver;
use Modules\Core\Observers\RoleObserver;
use Modules\Core\Observers\UserObserver;
use Modules\Core\Services\CoreCommandRegistrar;
use Modules\Core\Services\CoreDashboardService;
use Modules\Core\Services\CoreMenuRegistrar;
use Modules\Core\Services\CorePermissionRegistrar;
use Modules\Core\Services\CoreSettingsRegistrar;
use Modules\Core\Services\CoreSignalRegistrar;
use Modules\Core\Services\SuperAdminService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\Permission\PermissionRegistrar;

/**
 * Class CoreServiceProvider
 *
 * Bootstraps the Core module services, registrations, and configurations.
 */
class CoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Core';

    protected string $nameLower = 'core';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(
        InertiaSharedDataRegistry $sharedDataRegistry,
        MenuRegistry $menuRegistry,
        CommandRegistry $commandRegistry,
        PermissionRegistry $permissionRegistry,
        RoleRegistry $roleRegistry,
        SuperAdminService $superAdminService,
        DashboardWidgetRegistry $widgetRegistry,
        SettingsRegistry $settingsRegistry,
        CoreMenuRegistrar $menuRegistrar,
        CoreCommandRegistrar $commandRegistrar,
        CoreDashboardService $dashboardService,
        CorePermissionRegistrar $permissionRegistrar,
        CoreSettingsRegistrar $settingsRegistrar,
        SignalHandlerRegistry $signalRegistry,
        CoreSignalRegistrar $signalRegistrar,
        UserPreferenceService $preferenceService
    ): void {
        app(PermissionRegistrar::class)->initializeCache();

        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $menuRegistrar->register($menuRegistry, $this->name);
        $commandRegistrar->register($commandRegistry, $this->name);
        $permissionRegistrar->registerRoles($roleRegistry);
        $permissionRegistrar->registerPermissions($permissionRegistry);
        if ($this->app->runningInConsole()) {
            $superAdminService->ensureExists($roleRegistry);
        }
        $dashboardService->registerWidgets($widgetRegistry, $this->name);
        $settingsRegistrar->register($settingsRegistry, $this->name);
        $signalRegistrar->register($signalRegistry);

        $sharedDataRegistry->register($this->name, function ($request) use ($menuRegistry, $commandRegistry, $preferenceService) {
            $user = $request->user();

            if ($user) {
                $user->loadMissing(['avatar', 'roles']);
            }

            return [
                'auth' => [
                    'user' => $user ? [
                        ...$user->toArray(),
                        'avatar_url' => $user->avatar?->url,
                    ] : null,
                ],
                'menus' => $menuRegistry->getMenus($user),
                'commands' => $commandRegistry->getCommands($user),
                'userPreferences' => $user ? [
                    'theme' => $preferenceService->get($user, 'core_theme', 'system'),
                    'notificationSound' => $preferenceService->get($user, 'core_notification_sound', true),
                    'notificationVolume' => (float) $preferenceService->get($user, 'core_notification_volume', 50) / 100,
                    'notificationDesktop' => $preferenceService->get($user, 'core_notification_desktop', false),
                ] : null,
            ];
        });

        $this->registerAuthorization();
        $this->registerObservers();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            BulkCreateUsersCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     *
     * @return void
     */
    protected function registerCommandSchedules(): void {}

    /**
     * Register translations.
     *
     * @return void
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
     *
     * @return void
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
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     *
     * @return void
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
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get publishable view paths.
     *
     * @return array<int, string>
     */
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
     *
     * @return void
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
     *
     * @return void
     */
    private function registerObservers(): void
    {
        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        Menu::observe(MenuObserver::class);
    }
}
