<?php

namespace Modules\AuditLog\Providers;

use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\AuditLog\App\Services\AuditLogDashboardService;
use Modules\AuditLog\Services\AuditLogMenuRegistrar;
use Modules\AuditLog\Services\AuditLogPermissionRegistrar;
use Modules\AuditLog\Services\AuditLogSettingsRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AuditLogServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AuditLog';

    protected string $nameLower = 'auditlog';

    /**
     * Boot the application events.
     */
    public function boot(
        MenuRegistry $menuRegistry,
        SettingsRegistry $settingsRegistry,
        PermissionRegistry $permissionRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        AuditLogMenuRegistrar $menuRegistrar,
        AuditLogDashboardService $dashboardService,
        AuditLogPermissionRegistrar $permissionRegistrar,
        AuditLogSettingsRegistrar $settingsRegistrar
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $menuRegistrar->register($menuRegistry, $this->name);
        $settingsRegistrar->register($settingsRegistry, $this->name);
        $permissionRegistrar->registerPermissions($permissionRegistry);
        $dashboardService->registerWidgets($widgetRegistry, $this->name);
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
        $this->commands([
            \Modules\AuditLog\App\Console\Commands\CleanupAuditLogsCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $scheduledCleanupEnabled = filter_var(settings('scheduled_cleanup_enabled', true), FILTER_VALIDATE_BOOLEAN);

            if ($scheduledCleanupEnabled) {
                $retentionDays = (int) settings('retention_days', 90);
                $scheduleTime = settings('cleanup_schedule_time', '02:00');

                if (! preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $scheduleTime)) {
                    $scheduleTime = '02:00';
                }

                Schedule::command('auditlog:cleanup', ['--days' => $retentionDays])
                    ->daily()
                    ->at($scheduleTime)
                    ->withoutOverlapping()
                    ->runInBackground();
            }
        });
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
}
