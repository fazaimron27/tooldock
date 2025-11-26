<?php

namespace App\Providers;

use App\Services\MenuRegistry;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MenuRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register default dashboard menu item
        app(MenuRegistry::class)->registerItem(
            group: 'Main',
            label: 'Dashboard',
            route: 'dashboard',
            icon: 'Home',
            order: 1
        );
    }
}
