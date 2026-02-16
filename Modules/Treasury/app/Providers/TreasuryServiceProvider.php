<?php

/**
 * Treasury Service Provider
 *
 * Main service provider for the Treasury module. Bootstraps all module
 * registrations including categories, commands, dashboard widgets,
 * menus, permissions, settings, signal handlers, user relationships,
 * views, translations, and scheduled commands.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Providers;

use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\CommandRegistry;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\InertiaSharedDataRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;
use Modules\Treasury\Services\Support\CurrencyFormatter;
use Modules\Treasury\Services\TreasuryCategoryRegistrar;
use Modules\Treasury\Services\TreasuryCommandRegistrar;
use Modules\Treasury\Services\TreasuryDashboardService;
use Modules\Treasury\Services\TreasuryMenuRegistrar;
use Modules\Treasury\Services\TreasuryPermissionRegistrar;
use Modules\Treasury\Services\TreasurySettingsRegistrar;
use Modules\Treasury\Services\TreasurySignalRegistrar;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class TreasuryServiceProvider
 *
 * Central service provider orchestrating all Treasury module bootstrapping.
 */
class TreasuryServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Treasury';

    protected string $nameLower = 'treasury';

    /**
     * Boot the application events.
     */
    public function boot(
        CategoryRegistry $categoryRegistry,
        CommandRegistry $commandRegistry,
        DashboardWidgetRegistry $widgetRegistry,
        InertiaSharedDataRegistry $sharedDataRegistry,
        MenuRegistry $menuRegistry,
        PermissionRegistry $permissionRegistry,
        SettingsRegistry $settingsRegistry,
        SignalHandlerRegistry $signalHandlerRegistry,
        TreasuryCategoryRegistrar $categoryRegistrar,
        TreasuryCommandRegistrar $commandRegistrar,
        TreasuryDashboardService $dashboardService,
        TreasuryMenuRegistrar $menuRegistrar,
        TreasuryPermissionRegistrar $permissionRegistrar,
        TreasurySettingsRegistrar $settingsRegistrar,
        TreasurySignalRegistrar $signalRegistrar,
        \App\Services\Registry\SignalCategoryRegistry $signalCategoryRegistry
    ): void {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $categoryRegistrar->register($categoryRegistry, $this->name);
        $commandRegistrar->register($commandRegistry, $this->name);
        $menuRegistrar->register($menuRegistry, $this->name);
        $permissionRegistrar->registerPermissions($permissionRegistry);
        $dashboardService->registerWidgets($widgetRegistry, $this->name);
        $settingsRegistrar->register($settingsRegistry, $this->name);
        $signalRegistrar->register($signalHandlerRegistry);

        $signalCategoryRegistry->register($this->name, 'treasury_budget', 'treasury_budget_notify_enabled');
        $signalCategoryRegistry->register($this->name, 'treasury_wallet', 'treasury_wallet_notify_enabled');
        $signalCategoryRegistry->register($this->name, 'treasury_goal', 'treasury_goal_notify_enabled');
        $signalCategoryRegistry->register($this->name, 'treasury_transaction', 'treasury_transaction_notify_enabled');
        $sharedDataRegistry->register($this->name, function ($request) {
            return [
                'currency_code' => settings('treasury_reference_currency', 'IDR'),
                'currency_map' => CurrencyFormatter::getCurrencyDefinitions(),
            ];
        });

        $this->registerUserRelationships();
    }

    /**
     * Register dynamic relationships on User model.
     *
     * This allows Treasury models to be accessed via User without
     * adding direct dependencies to the Core module.
     *
     * @return void
     */
    protected function registerUserRelationships(): void
    {
        User::resolveRelationUsing('wallets', function ($user) {
            return $user->hasMany(Wallet::class);
        });

        User::resolveRelationUsing('budgets', function ($user) {
            return $user->hasMany(Budget::class);
        });

        User::resolveRelationUsing('goals', function ($user) {
            return $user->hasMany(TreasuryGoal::class);
        });

        User::resolveRelationUsing('transactions', function ($user) {
            return $user->hasMany(Transaction::class);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Treasury\Console\Commands\ReconcileTreasuryBalances::class,
            \Modules\Treasury\Console\Commands\RefreshExchangeRatesCommand::class,
            \Modules\Treasury\Console\Commands\CheckBudgetRolloverDebtCommand::class,
            \Modules\Treasury\Console\Commands\CheckWalletInactivityCommand::class,
            \Modules\Treasury\Console\Commands\SendNetWorthSummaryCommand::class,
            \Modules\Treasury\Console\Commands\CheckGoalStatusCommand::class,
            \Modules\Treasury\Console\Commands\SendGoalSummaryCommand::class,
            \Modules\Treasury\Console\Commands\SendDailyTransactionSummaryCommand::class,
            \Modules\Treasury\Console\Commands\SendWeeklyTransactionSummaryCommand::class,
        ]);
    }

    /**
     * Register command schedules.
     *
     * @return void
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

            $schedule->command('treasury:refresh-rates')->daily();

            $schedule->command('treasury:check-rollover-debt')
                ->monthlyOn(1, '08:00')
                ->withoutOverlapping();

            $schedule->command('treasury:check-wallet-inactivity')
                ->weeklyOn(0, '09:00')
                ->withoutOverlapping();

            $schedule->command('treasury:send-networth-summary')
                ->monthlyOn(28, '20:00')
                ->withoutOverlapping();

            $schedule->command('treasury:check-goal-status')
                ->dailyAt('09:00')
                ->withoutOverlapping();

            $schedule->command('treasury:send-goal-summary')
                ->monthlyOn(28, '20:30')
                ->withoutOverlapping();

            $schedule->command('treasury:send-daily-summary')
                ->dailyAt('20:00')
                ->withoutOverlapping();

            $schedule->command('treasury:send-weekly-summary')
                ->weeklyOn(0, '19:00')
                ->withoutOverlapping();
        });
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
     * @param  string  $path  Absolute path to the config file
     * @param  string  $key  Config key to merge under
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
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get publishable view paths.
     *
     * @return array<string>
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
