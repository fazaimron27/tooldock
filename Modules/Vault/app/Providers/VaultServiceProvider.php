<?php

namespace Modules\Vault\Providers;

use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\InertiaSharedDataRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\MiddlewareRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Vault\Services\VaultCategoryRegistrar;
use Modules\Vault\Services\VaultDashboardService;
use Modules\Vault\Services\VaultMenuRegistrar;
use Modules\Vault\Services\VaultPermissionRegistrar;
use Modules\Vault\Services\VaultSettingsRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class VaultServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Vault';

    protected string $nameLower = 'vault';

    /**
     * Boot the application events.
     */
    public function boot(
        CategoryRegistry $categoryRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        InertiaSharedDataRegistry $sharedDataRegistry,
        MenuRegistry $menuRegistry,
        MiddlewareRegistry $middlewareRegistry,
        PermissionRegistry $permissionRegistry,
        SettingsRegistry $settingsRegistry,
        VaultCategoryRegistrar $categoryRegistrar,
        VaultDashboardService $dashboardService,
        VaultMenuRegistrar $menuRegistrar,
        VaultPermissionRegistrar $permissionRegistrar,
        VaultSettingsRegistrar $settingsRegistrar
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $categoryRegistrar->register($categoryRegistry, $this->name);
        $menuRegistrar->register($menuRegistry, $this->name);
        $permissionRegistrar->registerPermissions($permissionRegistry);
        $settingsRegistrar->register($settingsRegistry, $this->name);
        $dashboardService->registerWidgets($widgetRegistry, $this->name);
        if (class_exists(\App\Services\Registry\SignalCategoryRegistry::class)) {
            app(\App\Services\Registry\SignalCategoryRegistry::class)->register($this->name, 'vault', 'vault_notify_enabled');
        }

        $middlewareRegistry->register($this->name, \Modules\Vault\Http\Middleware\VaultLockMiddleware::class);

        $sharedDataRegistry->register($this->name, function ($request) {
            return [
                'vault_lock_settings' => [
                    'enabled' => settings('vault_lock_enabled', false),
                    'timeout' => settings('vault_lock_timeout', 15),
                    'unlocked' => $request->session()->get('vault_unlocked', false),
                    'unlocked_at' => $request->session()->get('vault_unlocked_at'),
                ],
            ];
        });
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
}
