<?php

namespace Modules\Media\Providers;

use App\Data\DashboardWidget;
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
use Modules\Settings\Enums\SettingType;
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
        DashboardWidgetRegistry $widgetRegistry
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $menuRegistry->registerItem(
            group: 'System',
            label: 'Media',
            route: 'media.index',
            icon: 'Image',
            order: 40,
            permission: 'media.files.view',
            parentKey: null,
            module: $this->name
        );

        // Register Media module dashboard as child of Dashboard
        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Media Dashboard',
            route: 'media.dashboard',
            icon: 'LayoutDashboard',
            order: 40,
            permission: 'media.dashboard.view',
            parentKey: 'dashboard',
            module: $this->name
        );

        $this->registerSettings($settingsRegistry);
        $this->registerDefaultPermissions($permissionRegistry);
        $this->registerRateLimiter();

        // Register model observers
        $this->bootObservers();

        // Stat Widget: Total Media Files (Overview - shown on main dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Media Files',
                value: fn () => MediaFile::permanent()->count(),
                icon: 'Image',
                module: $this->name,
                order: 40,
                scope: 'overview'
            )
        );

        // System Widget: Storage Usage (Detail - shown on module dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'system',
                title: 'Storage Usage',
                value: 0,
                icon: 'HardDrive',
                module: $this->name,
                description: 'Media storage metrics',
                data: fn () => $this->getStorageUsageMetrics(),
                order: 41,
                scope: 'detail'
            )
        );

        // Activity Widget: Recent Media Uploads (Detail - shown on module dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Uploads',
                value: 0,
                icon: 'Upload',
                module: $this->name,
                description: 'Latest media file uploads',
                data: fn () => $this->getRecentMediaActivity(),
                order: 42,
                scope: 'detail'
            )
        );
    }

    /**
     * Get storage usage metrics for system widget.
     *
     * Optimized: Uses a single query with conditional aggregation instead of multiple queries.
     */
    private function getStorageUsageMetrics(): array
    {
        // Single query to get all metrics at once
        $metrics = MediaFile::selectRaw('
                COUNT(*) FILTER (WHERE is_temporary = false) as permanent_count,
                COUNT(*) FILTER (WHERE is_temporary = true) as temporary_count,
                COALESCE(SUM(size) FILTER (WHERE is_temporary = false), 0) as total_size
            ')
            ->first();

        $totalFiles = (int) ($metrics->permanent_count ?? 0);
        $totalSize = (int) ($metrics->total_size ?? 0);
        $temporaryFiles = (int) ($metrics->temporary_count ?? 0);

        // Convert bytes to MB
        $totalSizeMB = round($totalSize / 1024 / 1024, 2);

        // Calculate percentages (assuming 10GB max for demo)
        $maxStorage = 10 * 1024; // 10GB in MB
        $usagePercentage = $maxStorage > 0 ? round(($totalSizeMB / $maxStorage) * 100, 1) : 0;

        return [
            [
                'label' => 'Total Storage Used',
                'value' => "{$totalSizeMB} MB",
                'percentage' => min($usagePercentage, 100),
                'color' => $usagePercentage > 80 ? 'destructive' : ($usagePercentage > 60 ? 'warning' : 'success'),
            ],
            [
                'label' => 'Permanent Files',
                'value' => (string) $totalFiles,
                'percentage' => $totalFiles > 0 ? 100 : 0,
                'color' => 'primary',
            ],
            [
                'label' => 'Temporary Files',
                'value' => (string) $temporaryFiles,
                'percentage' => $temporaryFiles > 0 ? 50 : 0,
                'color' => 'warning',
            ],
        ];
    }

    /**
     * Get recent media uploads activity for activity widget.
     */
    private function getRecentMediaActivity(): array
    {
        return MediaFile::permanent()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($file) {
                $fileSize = round($file->size / 1024, 2); // KB

                return [
                    'id' => $file->id,
                    'title' => "Uploaded: {$file->filename} ({$fileSize} KB)",
                    'timestamp' => $file->created_at->diffForHumans(),
                    'icon' => 'Upload',
                    'iconColor' => 'bg-purple-500',
                ];
            })
            ->toArray();
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
     * Register media module settings.
     */
    private function registerSettings(SettingsRegistry $registry): void
    {
        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'max_file_size',
            value: '10240',
            type: SettingType::Integer,
            label: 'Max File Size (KB)',
            isSystem: false
        );

        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'default_storage_disk',
            value: 'public',
            type: SettingType::Text,
            label: 'Default Storage Disk',
            isSystem: false
        );

        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'temporary_file_retention_hours',
            value: '24',
            type: SettingType::Integer,
            label: 'Temporary File Retention (Hours)',
            isSystem: false
        );

        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'image_max_dimension',
            value: '2000',
            type: SettingType::Integer,
            label: 'Image Max Dimension (px)',
            isSystem: false
        );

        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'image_quality',
            value: '85',
            type: SettingType::Integer,
            label: 'Image Quality (1-100)',
            isSystem: false
        );

        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'allowed_mime_types',
            value: 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf',
            type: SettingType::Text,
            label: 'Allowed MIME Types (comma-separated)',
            isSystem: false
        );

        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'prefer_webp',
            value: '0',
            type: SettingType::Integer,
            label: 'Prefer WebP Format (0=No, 1=Yes)',
            isSystem: false
        );

        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'upload_rate_limit_per_minute',
            value: '10',
            type: SettingType::Integer,
            label: 'Upload Rate Limit (per minute for authenticated users)',
            isSystem: false
        );

        $registry->register(
            module: 'Media',
            group: 'media',
            key: 'upload_rate_limit_guest_per_minute',
            value: '5',
            type: SettingType::Integer,
            label: 'Upload Rate Limit (per minute for guests)',
            isSystem: false
        );
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

    /**
     * Register default permissions for the Media module.
     */
    private function registerDefaultPermissions(PermissionRegistry $registry): void
    {
        $registry->register('media', [
            'dashboard.view',
            'files.view',
            'files.upload',
            'files.edit',
            'files.delete',
        ], [
            'Administrator' => ['dashboard.view', 'files.*'],
            'Staff' => ['dashboard.view', 'files.view', 'files.upload'],
        ]);
    }
}
