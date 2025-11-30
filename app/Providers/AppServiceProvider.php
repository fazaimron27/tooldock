<?php

namespace App\Providers;

use App\Listeners\AutoInstallProtectedModules;
use App\Services\MenuRegistry;
use App\Services\SettingsRegistry;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;

/**
 * Application service provider
 *
 * Registers application-wide services and bootstraps core functionality.
 * Handles menu registration, Vite configuration, and module auto-installation.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services
     *
     * Registers singleton services that should be available throughout the application.
     */
    public function register(): void
    {
        $this->app->singleton(MenuRegistry::class);
        $this->app->singleton(SettingsRegistry::class);
    }

    /**
     * Bootstrap any application services
     *
     * Configures Vite prefetching, registers default menu items, and sets up
     * event listeners for module auto-installation after migrations.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Module::scan();
        $allModules = Module::all();

        foreach ($allModules as $module) {
            if ($module->get('protected') === true) {
                $migrationPath = $module->getPath().'/database/migrations';
                if (is_dir($migrationPath)) {
                    $this->loadMigrationsFrom($migrationPath);
                }
            }
        }

        app(MenuRegistry::class)->registerItem(
            group: 'Main',
            label: 'Dashboard',
            route: 'dashboard',
            icon: 'Home',
            order: 1
        );

        Event::listen(
            MigrationsEnded::class,
            AutoInstallProtectedModules::class
        );

        if (function_exists('settings') && ! $this->isRunningMigrations()) {
            try {
                $appName = settings('app_name', config('app.name', 'Laravel'));
                config(['app.name' => $appName]);

                $appDebug = settings('app_debug', config('app.debug', false));
                config(['app.debug' => filter_var($appDebug, FILTER_VALIDATE_BOOLEAN)]);
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * Check if we're currently running migrations.
     */
    private function isRunningMigrations(): bool
    {
        if (! app()->runningInConsole()) {
            return false;
        }

        $command = $_SERVER['argv'][1] ?? '';

        return str_contains($command, 'migrate');
    }
}
