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
use App\Services\Core\UserPreferenceService;
use App\Services\Registry\CommandRegistry;
use App\Services\Registry\InertiaSharedDataRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\SignalCategoryRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Signal\Services\SignalCacheService;
use Modules\Signal\Services\SignalCommandRegistrar;
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
 * Bootstraps the Signal module services, registrations, and configurations.
 */
class SignalServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Signal';

    protected string $nameLower = 'signal';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(
        CommandRegistry $commandRegistry,
        InertiaSharedDataRegistry $sharedDataRegistry,
        PermissionRegistry $permissionRegistry,
        SettingsRegistry $settingsRegistry,
        SignalCategoryRegistry $categoryRegistry,
        SignalCommandRegistrar $commandRegistrar,
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
        $commandRegistrar->register($commandRegistry, $this->name);
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
     * @return void
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
        $this->app->singleton(SignalCacheService::class, function ($app) {
            return new SignalCacheService($app->make(CacheService::class));
        });
        $this->app->singleton(SignalPreferenceService::class, function ($app) {
            return new SignalPreferenceService(
                $app->make(SignalCategoryRegistry::class),
                $app->make(UserPreferenceService::class)
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
     * Register commands in the format of Command::class.
     *
     * @return void
     */
    protected function registerCommands(): void {}

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
        return [
            SignalService::class,
            SignalCacheService::class,
        ];
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
}
