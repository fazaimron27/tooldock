<?php

/**
 * Bot Service Provider
 *
 * Main service provider for the Bot module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Providers;

use App\Services\Registry\BotCommandRegistryInterface;
use App\Services\Registry\CommandRegistry;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\HookInboundProcessorRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\SignalCategoryRegistry;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Bot\Console\Commands\SyncDiscordCommandsCommand;
use Modules\Bot\Observers\BotHookInboundObserver;
use Modules\Bot\Processors\DiscordInboundProcessor;
use Modules\Bot\Processors\TelegramInboundProcessor;
use Modules\Bot\Services\BotBotRegistrar;
use Modules\Bot\Services\BotCommandRegistrar;
use Modules\Bot\Services\BotCommandRegistry;
use Modules\Bot\Services\BotDashboardService;
use Modules\Bot\Services\BotDriverFactory;
use Modules\Bot\Services\BotManager;
use Modules\Bot\Services\BotMenuRegistrar;
use Modules\Bot\Services\BotPermissionRegistrar;
use Modules\Bot\Services\BotSettingsRegistrar;
use Modules\Bot\Services\BotSignalRegistrar;
use Modules\Hook\Models\HookInbound;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BotServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Bot';

    protected string $nameLower = 'bot';

    /**
     * Boot the application events.
     */
    public function boot(
        CommandRegistry $commandRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        MenuRegistry $menuRegistry,
        PermissionRegistry $permissionRegistry,
        SettingsRegistry $settingsRegistry,
        SignalHandlerRegistry $signalHandlerRegistry,
        SignalCategoryRegistry $signalCategoryRegistry,
        BotCommandRegistrar $commandRegistrar,
        BotDashboardService $dashboardService,
        BotMenuRegistrar $menuRegistrar,
        BotPermissionRegistrar $permissionRegistrar,
        BotSettingsRegistrar $settingsRegistrar,
        BotSignalRegistrar $signalRegistrar,
        HookInboundProcessorRegistry $processorRegistry,
        BotBotRegistrar $botBotRegistrar,
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
        $signalRegistrar->register($signalHandlerRegistry);
        $signalCategoryRegistry->register($this->name, 'bot', 'bot_notify_enabled');

        // Register the Bot module's own built-in bot commands (/whoami, etc.)
        $botBotRegistrar->register(app(BotCommandRegistryInterface::class));

        // Self-heal: recreate HookInbound if deleted from the Hook UI
        HookInbound::observe(BotHookInboundObserver::class);

        // Register platform authenticity checks into the Hook inbound pipeline.
        $processorRegistry->register(new DiscordInboundProcessor);
        $processorRegistry->register(new TelegramInboundProcessor);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);

        $this->app->singleton(BotCommandRegistry::class);
        $this->app->bind(BotCommandRegistryInterface::class, BotCommandRegistry::class);

        $this->app->singleton(BotDriverFactory::class);
        $this->app->singleton(BotManager::class, fn ($app) => new BotManager($app->make(BotDriverFactory::class)));

        $this->app->alias(BotManager::class, 'bot');
    }

    protected function registerCommands(): void
    {
        $this->commands([
            SyncDiscordCommandsCommand::class,
        ]);
    }

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
                    $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$configKey);

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
