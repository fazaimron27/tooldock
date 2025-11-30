<?php

namespace Modules\Newsletter\Providers;

use App\Services\MenuRegistry;
use App\Services\SettingsRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Settings\Enums\SettingType;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class NewsletterServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Newsletter';

    protected string $nameLower = 'newsletter';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        app(MenuRegistry::class)->registerItem(
            group: 'Content',
            label: 'Newsletter',
            route: 'newsletter.index',
            icon: 'Send',
            order: 20,
            permission: 'newsletter.campaigns.view'
        );

        $this->registerSettings();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
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
     * Register newsletter module settings.
     *
     * Group name should be lowercase module name for consistency.
     */
    private function registerSettings(): void
    {
        $registry = app(SettingsRegistry::class);

        $registry->register(
            module: 'Newsletter',
            group: 'newsletter',
            key: 'campaigns_per_page',
            value: '10',
            type: SettingType::Integer,
            label: 'Campaigns Per Page',
            isSystem: false
        );

        $registry->register(
            module: 'Newsletter',
            group: 'newsletter',
            key: 'max_posts_per_campaign',
            value: '20',
            type: SettingType::Integer,
            label: 'Maximum Posts Per Campaign',
            isSystem: false
        );

        $registry->register(
            module: 'Newsletter',
            group: 'newsletter',
            key: 'newsletter_default_sort',
            value: 'created_at',
            type: SettingType::Text,
            label: 'Default Sort Column',
            isSystem: false
        );

        $registry->register(
            module: 'Newsletter',
            group: 'newsletter',
            key: 'newsletter_default_sort_direction',
            value: 'desc',
            type: SettingType::Text,
            label: 'Default Sort Direction',
            isSystem: false
        );
    }
}
