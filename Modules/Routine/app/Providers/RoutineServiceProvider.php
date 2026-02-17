<?php

/**
 * Routine Service Provider
 *
 * Bootstraps the Routine module: routes, views, config, migrations,
 * menus, permissions, and dashboard widgets.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Providers;

use App\Services\Registry\CommandRegistry;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Routine\Services\RoutineCommandRegistrar;
use Modules\Routine\Services\RoutineDashboardService;
use Modules\Routine\Services\RoutineMenuRegistrar;
use Modules\Routine\Services\RoutinePermissionRegistrar;
use Modules\Routine\Services\RoutineSettingsRegistrar;
use Modules\Routine\Services\RoutineSignalRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class RoutineServiceProvider
 *
 * Main service provider responsible for registering all Routine module
 * components including routes, menus, permissions, and dashboard widgets.
 */
class RoutineServiceProvider extends ServiceProvider
{
    use PathNamespace;

    /**
     * @var string
     */
    protected string $name = 'Routine';

    /**
     * @var string
     */
    protected string $nameLower = 'routine';

    /**
     * Boot the application events.
     *
     * @param  CommandRegistry  $commandRegistry
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @param  MenuRegistry  $menuRegistry
     * @param  PermissionRegistry  $permissionRegistry
     * @param  SettingsRegistry  $settingsRegistry
     * @param  SignalHandlerRegistry  $signalHandlerRegistry
     * @return void
     */
    public function boot(
        CommandRegistry $commandRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        MenuRegistry $menuRegistry,
        PermissionRegistry $permissionRegistry,
        SettingsRegistry $settingsRegistry,
        SignalHandlerRegistry $signalHandlerRegistry,
    ): void {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $this->bootCommands($commandRegistry);
        $this->bootMenus($menuRegistry);
        $this->bootPermissions($permissionRegistry);
        $this->bootSettings($settingsRegistry);
        $this->bootSignals($signalHandlerRegistry);
        $this->bootSignalCategories();
        $this->bootDashboardWidgets($widgetRegistry);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register command palette entries via the command registrar.
     *
     * @param  CommandRegistry  $commandRegistry
     * @return void
     */
    protected function bootCommands(CommandRegistry $commandRegistry): void
    {
        (new RoutineCommandRegistrar)->register($commandRegistry, $this->name);
    }

    /**
     * Register menu items via the menu registrar.
     *
     * @param  MenuRegistry  $menuRegistry
     * @return void
     */
    protected function bootMenus(MenuRegistry $menuRegistry): void
    {
        (new RoutineMenuRegistrar)->register($menuRegistry, $this->name);
    }

    /**
     * Register permissions via the permission registrar.
     *
     * @param  PermissionRegistry  $permissionRegistry
     * @return void
     */
    protected function bootPermissions(PermissionRegistry $permissionRegistry): void
    {
        (new RoutinePermissionRegistrar)->register($permissionRegistry);
    }

    /**
     * Register settings via the settings registrar.
     *
     * @param  SettingsRegistry  $settingsRegistry
     * @return void
     */
    protected function bootSettings(SettingsRegistry $settingsRegistry): void
    {
        (new RoutineSettingsRegistrar)->register($settingsRegistry, $this->name);
    }

    /**
     * Register signal handlers via the signal registrar.
     *
     * @param  SignalHandlerRegistry  $signalHandlerRegistry
     * @return void
     */
    protected function bootSignals(SignalHandlerRegistry $signalHandlerRegistry): void
    {
        (new RoutineSignalRegistrar)->register($signalHandlerRegistry);
    }

    /**
     * Register signal categories for notification preference checking.
     *
     * Maps the 'routine' notification category to the 'routine_notify_enabled'
     * user setting so the Signal pipeline respects user preferences.
     *
     * @return void
     */
    protected function bootSignalCategories(): void
    {
        if (class_exists(\App\Services\Registry\SignalCategoryRegistry::class)) {
            app(\App\Services\Registry\SignalCategoryRegistry::class)
                ->register($this->name, 'routine', 'routine_notify_enabled');
        }
    }

    /**
     * Register dashboard widgets via the dashboard service.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @return void
     */
    protected function bootDashboardWidgets(DashboardWidgetRegistry $widgetRegistry): void
    {
        (new RoutineDashboardService)->registerWidgets($widgetRegistry, $this->name);
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
}
