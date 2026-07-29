<?php

/**
 * Hook Service Provider.
 *
 * Main service provider for the Hook module. Registers commands,
 * configuration, views, translations, and all module registries.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Providers;

use App\Services\Registry\CommandRegistry;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\HookEventRegistryInterface;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\SignalCategoryRegistry;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Hook\Observers\HookModelObserver;
use Modules\Hook\Services\HookCommandRegistrar;
use Modules\Hook\Services\HookDashboardService;
use Modules\Hook\Services\HookEventRegistry;
use Modules\Hook\Services\HookMenuRegistrar;
use Modules\Hook\Services\HookPermissionRegistrar;
use Modules\Hook\Services\HookSettingsRegistrar;
use Modules\Hook\Services\HookSignalRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class HookServiceProvider
 *
 * Bootstraps the Hook module: registers sub-providers, menu items,
 * permissions, rate limiters, and all standard module resources.
 */
class HookServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Hook';

    protected string $nameLower = 'hook';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(
        CommandRegistry $commandRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        MenuRegistry $menuRegistry,
        PermissionRegistry $permissionRegistry,
        SettingsRegistry $settingsRegistry,
        SignalHandlerRegistry $signalHandlerRegistry,
        SignalCategoryRegistry $signalCategoryRegistry,
        HookCommandRegistrar $commandRegistrar,
        HookDashboardService $dashboardService,
        HookMenuRegistrar $menuRegistrar,
        HookPermissionRegistrar $permissionRegistrar,
        HookSettingsRegistrar $settingsRegistrar,
        HookSignalRegistrar $signalRegistrar
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $commandRegistrar->register($commandRegistry, $this->name);
        $menuRegistrar->register($menuRegistry, $this->name);
        $permissionRegistrar->registerPermissions($permissionRegistry);
        $settingsRegistrar->register($settingsRegistry, $this->name);
        $dashboardService->registerWidgets($widgetRegistry, $this->name);

        $this->registerRateLimiters();
        $this->bootHookEventRegistry();

        $signalRegistrar->register($signalHandlerRegistry);
        $signalCategoryRegistry->register($this->name, 'hook_inbound', 'hook_inbound_notify_enabled');
        $signalCategoryRegistry->register($this->name, 'hook_outbound', 'hook_outbound_notify_enabled');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);

        $this->app->singleton(HookEventRegistry::class);
        $this->app->bind(HookEventRegistryInterface::class, HookEventRegistry::class);
    }

    /**
     * Boot the Hook event registry.
     *
     * Wires the HookModelObserver to every model class that has been
     * registered as a hookable trigger. Registrars are now called from
     * each module's own service provider — Hook has no knowledge of them.
     */
    protected function bootHookEventRegistry(): void
    {
        $registry = $this->app->make(HookEventRegistry::class);

        $observer = $this->app->make(HookModelObserver::class);

        foreach ($registry->observedModelClasses() as $modelClass) {
            $modelClass::observe($observer);
        }
    }

    /**
     * Register rate limiters for public webhook catch endpoints.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('hook-catch', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->route('slug').'|'.$request->ip()
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
}
