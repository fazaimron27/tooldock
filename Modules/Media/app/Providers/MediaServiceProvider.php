<?php

namespace Modules\Media\Providers;

use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Media\Console\CleanupTemporaryMedia;
use Modules\Media\Models\MediaFile;
use Modules\Media\Observers\MediaFileObserver;
use Modules\Media\Services\MediaDashboardService;
use Modules\Media\Services\MediaMenuRegistrar;
use Modules\Media\Services\MediaPermissionRegistrar;
use Modules\Media\Services\MediaSettingsRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MediaServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Media';

    protected string $nameLower = 'media';

    /**
     * Boot the application events.
     */
    public function boot(
        MenuRegistry $menuRegistry,
        SettingsRegistry $settingsRegistry,
        PermissionRegistry $permissionRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        MediaMenuRegistrar $menuRegistrar,
        MediaDashboardService $dashboardService,
        MediaPermissionRegistrar $permissionRegistrar,
        MediaSettingsRegistrar $settingsRegistrar
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
        $this->registerRateLimiter();
        $this->bootObservers();
        $dashboardService->registerWidgets($widgetRegistry, $this->name);
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
     * Register model observers.
     */
    public function bootObservers(): void
    {
        MediaFile::observe(MediaFileObserver::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            CleanupTemporaryMedia::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            $schedule->command('media:cleanup-temporary')->daily();
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

    /**
     * Register rate limiter for media uploads.
     */
    private function registerRateLimiter(): void
    {
        RateLimiter::for('media-uploads', function (Request $request) {
            $userLimit = function_exists('settings')
                ? max(1, (int) settings('upload_rate_limit_per_minute', 10))
                : 10;
            $guestLimit = function_exists('settings')
                ? max(1, (int) settings('upload_rate_limit_guest_per_minute', 5))
                : 5;

            return $request->user()
                ? Limit::perMinute($userLimit)->by($request->user()->id)
                : Limit::perMinute($guestLimit)->by($request->ip());
        });
    }
}
