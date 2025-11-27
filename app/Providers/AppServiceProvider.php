<?php

namespace App\Providers;

use App\Listeners\AutoInstallProtectedModules;
use App\Services\MenuRegistry;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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

        app(MenuRegistry::class)->registerItem(
            group: 'Main',
            label: 'Dashboard',
            route: 'dashboard',
            icon: 'Home',
            order: 1
        );

        // Auto-install protected modules after migrations complete
        Event::listen(
            MigrationsEnded::class,
            AutoInstallProtectedModules::class
        );
    }
}
