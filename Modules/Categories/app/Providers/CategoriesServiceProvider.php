<?php

namespace Modules\Categories\Providers;

use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Constants\Roles as RoleConstants;
use Modules\Settings\Enums\SettingType;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CategoriesServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Categories';

    protected string $nameLower = 'categories';

    /**
     * Boot the application events.
     */
    public function boot(
        MenuRegistry $menuRegistry,
        SettingsRegistry $settingsRegistry,
        CategoryRegistry $categoryRegistry,
        PermissionRegistry $permissionRegistry
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $menuRegistry->registerItem(
            group: 'Master Data',
            label: 'Categories',
            route: 'categories.index',
            icon: 'Tag',
            order: 10,
            permission: 'categories.category.view',
            parentKey: null,
            module: $this->name
        );

        $this->registerSettings($settingsRegistry);
        $this->registerDefaultCategories($categoryRegistry);
        $this->registerDefaultPermissions($permissionRegistry);
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
    protected function registerCommands(): void {}

    /**
     * Register command Schedules.
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

    /**
     * Register categories module settings.
     *
     * Group name should be lowercase module name for consistency.
     */
    private function registerSettings(SettingsRegistry $registry): void
    {
        $registry->register(
            module: 'Categories',
            group: 'categories',
            key: 'categories_per_page',
            value: '20',
            type: SettingType::Integer,
            label: 'Categories Per Page',
            isSystem: false
        );

        $registry->register(
            module: 'Categories',
            group: 'categories',
            key: 'categories_default_sort',
            value: 'created_at',
            type: SettingType::Text,
            label: 'Default Sort Column',
            isSystem: false
        );

        $registry->register(
            module: 'Categories',
            group: 'categories',
            key: 'categories_default_sort_direction',
            value: 'desc',
            type: SettingType::Text,
            label: 'Default Sort Direction',
            isSystem: false
        );

        $registry->register(
            module: 'Categories',
            group: 'categories',
            key: 'categories_default_type',
            value: '',
            type: SettingType::Text,
            label: 'Default Type Filter',
            isSystem: false
        );

        $registry->register(
            module: 'Categories',
            group: 'categories',
            key: 'categories_default_types',
            value: 'product,finance,project,inventory,expense,department',
            type: SettingType::Text,
            label: 'Default Category Types (comma-separated)',
            isSystem: false
        );
    }

    /**
     * Register default categories for the Categories module.
     *
     * These are sample categories for development/testing purposes.
     * Other modules can register their own categories using CategoryRegistry.
     */
    private function registerDefaultCategories(CategoryRegistry $registry): void
    {
        $registry->registerMany('Categories', 'product', [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'color' => '#3B82F6',
                'description' => 'Electronic devices and components',
            ],
            [
                'name' => 'Smartphones',
                'slug' => 'smartphones',
                'parent_slug' => 'electronics',
                'color' => '#2563EB',
                'description' => 'Mobile phones and smartphones',
            ],
            [
                'name' => 'Laptops',
                'slug' => 'laptops',
                'parent_slug' => 'electronics',
                'color' => '#1D4ED8',
                'description' => 'Laptop computers and accessories',
            ],
            [
                'name' => 'Clothing',
                'slug' => 'clothing',
                'color' => '#EC4899',
                'description' => 'Apparel and fashion items',
            ],
            [
                'name' => 'Men\'s Wear',
                'slug' => 'mens-wear',
                'parent_slug' => 'clothing',
                'color' => '#DB2777',
                'description' => 'Men\'s clothing and accessories',
            ],
            [
                'name' => 'Women\'s Wear',
                'slug' => 'womens-wear',
                'parent_slug' => 'clothing',
                'color' => '#BE185D',
                'description' => 'Women\'s clothing and accessories',
            ],
        ]);

        $registry->registerMany('Categories', 'finance', [
            [
                'name' => 'Income',
                'slug' => 'income',
                'color' => '#10B981',
                'description' => 'Revenue and income sources',
            ],
            [
                'name' => 'Sales Revenue',
                'slug' => 'sales-revenue',
                'parent_slug' => 'income',
                'color' => '#059669',
                'description' => 'Revenue from product sales',
            ],
            [
                'name' => 'Service Revenue',
                'slug' => 'service-revenue',
                'parent_slug' => 'income',
                'color' => '#047857',
                'description' => 'Revenue from services provided',
            ],
            [
                'name' => 'Operating Expenses',
                'slug' => 'operating-expenses',
                'color' => '#EF4444',
                'description' => 'Day-to-day business expenses',
            ],
            [
                'name' => 'Salaries',
                'slug' => 'salaries',
                'parent_slug' => 'operating-expenses',
                'color' => '#DC2626',
                'description' => 'Employee salaries and wages',
            ],
            [
                'name' => 'Utilities',
                'slug' => 'utilities',
                'parent_slug' => 'operating-expenses',
                'color' => '#B91C1C',
                'description' => 'Electricity, water, internet, etc.',
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'parent_slug' => 'operating-expenses',
                'color' => '#991B1B',
                'description' => 'Marketing and advertising expenses',
            ],
        ]);

        $registry->registerMany('Categories', 'project', [
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'color' => '#8B5CF6',
                'description' => 'Website and web application projects',
            ],
            [
                'name' => 'Mobile App',
                'slug' => 'mobile-app',
                'color' => '#7C3AED',
                'description' => 'Mobile application development projects',
            ],
            [
                'name' => 'Infrastructure',
                'slug' => 'infrastructure',
                'color' => '#6D28D9',
                'description' => 'IT infrastructure and system projects',
            ],
        ]);

        $registry->registerMany('Categories', 'inventory', [
            [
                'name' => 'Raw Materials',
                'slug' => 'raw-materials',
                'color' => '#F59E0B',
                'description' => 'Raw materials and components',
            ],
            [
                'name' => 'Finished Goods',
                'slug' => 'finished-goods',
                'color' => '#D97706',
                'description' => 'Completed products ready for sale',
            ],
            [
                'name' => 'Work in Progress',
                'slug' => 'work-in-progress',
                'color' => '#B45309',
                'description' => 'Items currently in production',
            ],
        ]);

        $registry->registerMany('Categories', 'expense', [
            [
                'name' => 'Office Supplies',
                'slug' => 'office-supplies',
                'color' => '#06B6D4',
                'description' => 'Office equipment and supplies',
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'color' => '#0891B2',
                'description' => 'Business travel expenses',
            ],
            [
                'name' => 'Training',
                'slug' => 'training',
                'color' => '#0E7490',
                'description' => 'Employee training and development',
            ],
        ]);

        $registry->registerMany('Categories', 'department', [
            [
                'name' => 'Sales',
                'slug' => 'sales',
                'color' => '#14B8A6',
                'description' => 'Sales department',
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'color' => '#0D9488',
                'description' => 'Marketing department',
            ],
            [
                'name' => 'IT',
                'slug' => 'it',
                'color' => '#0F766E',
                'description' => 'Information Technology department',
            ],
            [
                'name' => 'HR',
                'slug' => 'hr',
                'color' => '#115E59',
                'description' => 'Human Resources department',
            ],
            [
                'name' => 'Finance',
                'slug' => 'finance',
                'color' => '#134E4A',
                'description' => 'Finance and accounting department',
            ],
        ]);
    }

    /**
     * Register default permissions for the Categories module.
     */
    private function registerDefaultPermissions(PermissionRegistry $registry): void
    {
        $registry->register('categories', [
            'category.view',
            'category.create',
            'category.edit',
            'category.delete',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'category.*',
            ],
            RoleConstants::MANAGER => [
                'category.*',
            ],
        ]);
    }
}
