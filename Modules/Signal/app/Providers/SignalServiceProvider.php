<?php

/**
 * Signal Service Provider
 *
 * Main service provider for the Signal notification module.
 * Handles service registration, configuration loading, view registration,
 * and integration with application registries for menus, permissions,
 * and Inertia shared data.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Providers;

use App\Services\Cache\CacheService;
use App\Services\Registry\InertiaSharedDataRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Signal\Services\SignalCacheService;
use Modules\Signal\Services\SignalPermissionRegistrar;
use Modules\Signal\Services\SignalPreferenceService;
use Modules\Signal\Services\SignalService;
use Modules\Signal\Services\SignalSettingsRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class SignalServiceProvider
 *
 * Primary service provider that bootstraps the Signal module.
 * Registers services as singletons, loads configurations and translations,
 * registers views and Blade components, and integrates with application
 * registries for permissions, settings, and shared Inertia data.
 *
 * @see \Modules\Signal\Services\SignalService Main notification service
 * @see \Modules\Signal\Services\SignalCacheService Cache management service
 */
class SignalServiceProvider extends ServiceProvider
{
    use PathNamespace;

    /**
     * The module name used for configuration and path resolution.
     *
     * @var string
     */
    protected string $name = 'Signal';

    /**
     * Lowercase module name for namespace operations.
     *
     * @var string
     */
    protected string $nameLower = 'signal';

    /**
     * Boot the application events and module components.
     *
     * Initializes all module components including commands, translations,
     * configurations, views, and migrations. Registers permissions and
     * settings via registry services and sets up Inertia shared data
     * for unread notification counts.
     *
     * @param  InertiaSharedDataRegistry  $sharedDataRegistry  Registry for Inertia shared data
     * @param  PermissionRegistry  $permissionRegistry  Registry for module permissions
     * @param  SettingsRegistry  $settingsRegistry  Registry for module settings
     * @param  \App\Services\Registry\SignalCategoryRegistry  $categoryRegistry  Notification category registry
     * @param  SignalPermissionRegistrar  $permissionRegistrar  Permission registration service
     * @param  SignalSettingsRegistrar  $settingsRegistrar  Settings registration service
     * @param  SignalCacheService  $cacheService  Cache management service
     * @return void
     */
    public function boot(
        InertiaSharedDataRegistry $sharedDataRegistry,
        PermissionRegistry $permissionRegistry,
        SettingsRegistry $settingsRegistry,
        \App\Services\Registry\SignalCategoryRegistry $categoryRegistry,
        SignalPermissionRegistrar $permissionRegistrar,
        SignalSettingsRegistrar $settingsRegistrar,
        SignalCacheService $cacheService
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $permissionRegistrar->registerPermissions($permissionRegistry);
        $settingsRegistrar->register($settingsRegistry, $this->name);
        $categoryRegistry->register($this->name, 'login', 'signal_notify_login');
        $categoryRegistry->register($this->name, 'security', 'signal_notify_security');
        $categoryRegistry->register($this->name, 'system', 'signal_notify_system');
        $sharedDataRegistry->register($this->name, function ($request) use ($cacheService) {
            $user = $request->user();

            if (! $user) {
                return [];
            }

            return [
                'signal' => [
                    'unread_count' => $cacheService->getUnreadCount($user),
                ],
            ];
        });
    }

    /**
     * Register the service provider.
     *
     * Binds module services to the container as singletons.
     * Registers sub-providers for authentication, events, and routing.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(SignalCacheService::class, function ($app) {
            return new SignalCacheService($app->make(CacheService::class));
        });
        $this->app->singleton(SignalPreferenceService::class, function ($app) {
            return new SignalPreferenceService(
                $app->make(\App\Services\Registry\SignalCategoryRegistry::class),
                $app->make(\App\Services\Core\SettingsService::class)
            );
        });
        $this->app->singleton(SignalService::class, function ($app) {
            return new SignalService(
                $app->make(SignalCacheService::class),
                $app->make(SignalPreferenceService::class)
            );
        });
    }

    /**
     * Register artisan commands for the module.
     *
     * Placeholder for module-specific console commands.
     * Currently no commands are registered.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command scheduling for the module.
     *
     * Placeholder for scheduled tasks specific to the Signal module.
     * Can be used for notification cleanup or digest generation.
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
     * Register module translations.
     *
     * Loads translation files from the module's lang directory.
     * Supports both namespace-based translations (__('signal::message'))
     * and JSON translations.
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
     * Register module configuration files.
     *
     * Recursively loads all PHP config files from the module's config
     * directory and merges them with the application configuration.
     * Handles publishing for vendor configuration customization.
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
     * Merge configuration from a file into the application config.
     *
     * Recursively merges module configuration with existing application
     * configuration, allowing partial overrides.
     *
     * @param  string  $path  The absolute path to the config file
     * @param  string  $key  The configuration key to merge into
     * @return void
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register module views and Blade components.
     *
     * Loads views from the module's resources/views directory and
     * registers Blade component namespaces for the module.
     * Supports view publishing for customization.
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
     * Returns an array of service classes that this provider provides.
     * Used for deferred loading optimization.
     *
     * @return array<int, string> Array of service class names
     */
    public function provides(): array
    {
        return [
            SignalService::class,
            SignalCacheService::class,
        ];
    }

    /**
     * Get paths to publishable view directories.
     *
     * Scans application view paths for published module views
     * that should be loaded alongside module source views.
     *
     * @return array<int, string> Array of view directory paths
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
