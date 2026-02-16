<?php

/**
 * Vault Service Provider
 *
 * Main service provider for the Vault module. Bootstraps all module
 * registrations including routes, views, config, permissions, menus,
 * commands, settings, dashboard widgets, middleware, and signals.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Providers;

use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\CommandRegistry;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\InertiaSharedDataRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\MiddlewareRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Vault\Services\VaultCategoryRegistrar;
use Modules\Vault\Services\VaultCommandRegistrar;
use Modules\Vault\Services\VaultDashboardService;
use Modules\Vault\Services\VaultMenuRegistrar;
use Modules\Vault\Services\VaultPermissionRegistrar;
use Modules\Vault\Services\VaultSettingsRegistrar;
use Modules\Vault\Services\VaultSignalRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class VaultServiceProvider
 *
 * Orchestrates the Vault module bootstrap process by delegating
 * to specialised registrar services for each integration point.
 *
 * @see \Modules\Vault\Services\VaultPermissionRegistrar
 * @see \Modules\Vault\Services\VaultMenuRegistrar
 */
class VaultServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Vault';

    protected string $nameLower = 'vault';

    /**
     * Boot the application events.
     *
     * @param  CategoryRegistry  $categoryRegistry  Central category registry
     * @param  CommandRegistry  $commandRegistry  Central command palette registry
     * @param  DashboardWidgetRegistry  $widgetRegistry  Central dashboard widget registry
     * @param  InertiaSharedDataRegistry  $sharedDataRegistry  Inertia shared data registry
     * @param  MenuRegistry  $menuRegistry  Central menu registry
     * @param  MiddlewareRegistry  $middlewareRegistry  Central middleware registry
     * @param  PermissionRegistry  $permissionRegistry  Central permission registry
     * @param  SettingsRegistry  $settingsRegistry  Central settings registry
     * @param  VaultCategoryRegistrar  $categoryRegistrar  Vault category registrar
     * @param  VaultCommandRegistrar  $commandRegistrar  Vault command registrar
     * @param  VaultDashboardService  $dashboardService  Vault dashboard service
     * @param  VaultMenuRegistrar  $menuRegistrar  Vault menu registrar
     * @param  VaultPermissionRegistrar  $permissionRegistrar  Vault permission registrar
     * @param  VaultSettingsRegistrar  $settingsRegistrar  Vault settings registrar
     * @param  SignalHandlerRegistry  $signalRegistry  Central signal handler registry
     * @param  VaultSignalRegistrar  $signalRegistrar  Vault signal registrar
     * @return void
     */
    public function boot(
        CategoryRegistry $categoryRegistry,
        CommandRegistry $commandRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        InertiaSharedDataRegistry $sharedDataRegistry,
        MenuRegistry $menuRegistry,
        MiddlewareRegistry $middlewareRegistry,
        PermissionRegistry $permissionRegistry,
        SettingsRegistry $settingsRegistry,
        VaultCategoryRegistrar $categoryRegistrar,
        VaultCommandRegistrar $commandRegistrar,
        VaultDashboardService $dashboardService,
        VaultMenuRegistrar $menuRegistrar,
        VaultPermissionRegistrar $permissionRegistrar,
        VaultSettingsRegistrar $settingsRegistrar,
        SignalHandlerRegistry $signalRegistry,
        VaultSignalRegistrar $signalRegistrar
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $categoryRegistrar->register($categoryRegistry, $this->name);
        $commandRegistrar->register($commandRegistry, $this->name);
        $menuRegistrar->register($menuRegistry, $this->name);
        $permissionRegistrar->registerPermissions($permissionRegistry);
        $settingsRegistrar->register($settingsRegistry, $this->name);
        $dashboardService->registerWidgets($widgetRegistry, $this->name);
        if (class_exists(\App\Services\Registry\SignalCategoryRegistry::class)) {
            app(\App\Services\Registry\SignalCategoryRegistry::class)->register($this->name, 'vault', 'vault_notify_enabled');
        }
        $signalRegistrar->register($signalRegistry);

        $middlewareRegistry->register($this->name, \Modules\Vault\Http\Middleware\VaultLockMiddleware::class);

        $sharedDataRegistry->register($this->name, function ($request) {
            $user = $request->user();
            $enabled = $user
                ? app(\App\Services\Core\UserPreferenceService::class)->get($user, 'vault_lock_enabled', false)
                : settings('vault_lock_enabled', false);
            $timeout = $user
                ? app(\App\Services\Core\UserPreferenceService::class)->get($user, 'vault_lock_timeout', 15)
                : settings('vault_lock_timeout', 15);

            return [
                'vault_lock_settings' => [
                    'enabled' => $enabled,
                    'timeout' => $timeout,
                    'unlocked' => $request->session()->get('vault_unlocked', false),
                    'unlocked_at' => $request->session()->get('vault_unlocked_at'),
                ],
            ];
        });
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
     * Register commands in the format of Command::class
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     *
     * @return void
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

                    // Remove duplicated adjacent segments
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
     *
     * @param  string  $path  Absolute path to the config file
     * @param  string  $key  The config key to merge into
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
     * Get publishable view paths from the application's view directories.
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
}
