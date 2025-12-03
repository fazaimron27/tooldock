<?php

namespace Modules\Blog\Providers;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Blog\Models\Post;
use Modules\Blog\Observers\PostObserver;
use Modules\Settings\Enums\SettingType;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BlogServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Blog';

    protected string $nameLower = 'blog';

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
            group: 'Content',
            label: 'Blog',
            route: 'blog.index',
            icon: 'FileText',
            order: 10,
            permission: 'blog.posts.view',
            parentKey: null,
            module: $this->name
        );

        // Register Blog module dashboard as child of Dashboard
        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Blog Dashboard',
            route: 'blog.dashboard',
            icon: 'LayoutDashboard',
            order: 20,
            permission: 'blog.dashboard.view',
            parentKey: 'dashboard',
            module: $this->name
        );

        $this->registerSettings($settingsRegistry);
        $this->registerDefaultPermissions($permissionRegistry);

        // Register model observers
        $this->bootObservers();

        // Stat Widget: Total Posts (Overview - shown on main dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Posts',
                value: fn () => Post::count(),
                icon: 'FileText',
                module: $this->name,
                order: 20,
                scope: 'overview'
            )
        );

        // Chart Widget: Posts Published Over Time (Detail - shown on module dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'Posts Published',
                value: 0,
                icon: 'BarChart3',
                module: $this->name,
                description: 'Posts published over the last 6 months',
                chartType: 'bar',
                data: fn () => $this->getPostsPublishedData(),
                order: 21,
                scope: 'detail'
            )
        );

        // Activity Widget: Recent Posts (Detail - shown on module dashboard)
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Posts',
                value: 0,
                icon: 'FileText',
                module: $this->name,
                description: 'Latest published posts',
                data: fn () => $this->getRecentPostsActivity(),
                order: 22,
                scope: 'detail'
            )
        );
    }

    /**
     * Get posts published data for chart widget.
     *
     * Optimized: Uses a single query with GROUP BY instead of 6 separate queries.
     * Always returns an array with 6 months of data, even if no posts exist.
     */
    private function getPostsPublishedData(): array
    {
        try {
            $now = now();
            $startDate = $now->copy()->subMonths(5)->startOfMonth();
            $endDate = $now->copy()->endOfMonth();

            // Single query to get counts grouped by month
            $results = Post::selectRaw('
                    DATE_TRUNC(\'month\', published_at)::date as month,
                    COUNT(*) as count
                ')
                ->whereNotNull('published_at')
                ->whereBetween('published_at', [$startDate, $endDate])
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    // Extract Y-m from date string (format: YYYY-MM-DD)
                    // Handle both string and date object formats
                    $monthValue = is_string($item->month) ? $item->month : (string) $item->month;

                    return substr($monthValue, 0, 7);
                })
                ->map(fn ($item) => (int) $item->count)
                ->toArray();

            // Build months array with data from query
            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $monthKey = $month->format('Y-m');

                $months[] = [
                    'name' => $month->format('M Y'),
                    'value' => $results[$monthKey] ?? 0,
                ];
            }

            return $months;
        } catch (\Throwable $e) {
            // Fallback: return empty data structure on error
            \Illuminate\Support\Facades\Log::error('BlogServiceProvider: Error getting posts published data', [
                'error' => $e->getMessage(),
            ]);

            // Return empty structure with 6 months
            $now = now();
            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $months[] = [
                    'name' => $month->format('M Y'),
                    'value' => 0,
                ];
            }

            return $months;
        }
    }

    /**
     * Get recent posts activity for activity widget.
     */
    private function getRecentPostsActivity(): array
    {
        return Post::whereNotNull('published_at')
            ->latest('published_at')
            ->limit(5)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => "Post published: {$post->title}",
                    'timestamp' => $post->published_at->diffForHumans(),
                    'icon' => 'FileText',
                    'iconColor' => 'bg-green-500',
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
        Post::observe(PostObserver::class);
    }

    protected function registerCommands(): void {}

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

    /**
     * Register blog module settings.
     *
     * Group name should be lowercase module name for consistency.
     */
    private function registerSettings(SettingsRegistry $registry): void
    {
        $registry->register(
            module: 'Blog',
            group: 'blog',
            key: 'posts_per_page',
            value: '10',
            type: SettingType::Integer,
            label: 'Posts Per Page',
            isSystem: false
        );

        $registry->register(
            module: 'Blog',
            group: 'blog',
            key: 'blog_default_sort',
            value: 'created_at',
            type: SettingType::Text,
            label: 'Default Sort Column',
            isSystem: false
        );

        $registry->register(
            module: 'Blog',
            group: 'blog',
            key: 'blog_default_sort_direction',
            value: 'desc',
            type: SettingType::Text,
            label: 'Default Sort Direction',
            isSystem: false
        );
    }

    /**
     * Register default permissions for the Blog module.
     */
    private function registerDefaultPermissions(PermissionRegistry $registry): void
    {
        $registry->register('blog', [
            'dashboard.view',
            'posts.view',
            'posts.create',
            'posts.edit',
            'posts.delete',
            'posts.publish',
        ], [
            'Administrator' => ['dashboard.view', 'posts.*'],
            'Staff' => ['dashboard.view', 'posts.view', 'posts.create'],
        ]);
    }
}
