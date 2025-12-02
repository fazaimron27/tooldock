<?php

namespace App\Providers;

use App\Listeners\AutoInstallProtectedModules;
use App\Services\Core\AppConfigService;
use App\Services\Core\ExceptionResponseService;
use App\Services\Core\InertiaSharedDataService;
use App\Services\Core\SettingsService;
use App\Services\Core\StorageLinkService;
use App\Services\Media\MediaConfigService;
use App\Services\Modules\ProtectedModuleMigrationService;
use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\RoleRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Modules\Core\App\Services\SuperAdminService;

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
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(CategoryRegistry::class);
        $this->app->singleton(RoleRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(SuperAdminService::class);
        $this->app->singleton(MediaConfigService::class);
        $this->app->singleton(AppConfigService::class);
        $this->app->singleton(InertiaSharedDataService::class);
        $this->app->singleton(StorageLinkService::class);
        $this->app->singleton(ProtectedModuleMigrationService::class);
        $this->app->singleton(ExceptionResponseService::class);
    }

    /**
     * Bootstrap any application services
     *
     * Configures Vite prefetching, registers default menu items, and sets up
     * event listeners for module auto-installation after migrations.
     */
    public function boot(
        ProtectedModuleMigrationService $migrationService,
        MenuRegistry $menuRegistry,
        StorageLinkService $storageLinkService,
        AppConfigService $appConfigService
    ): void {
        Vite::prefetch(concurrency: 3);

        $migrationPaths = $migrationService->getMigrationPaths();
        foreach ($migrationPaths as $path) {
            $this->loadMigrationsFrom($path);
        }

        $menuRegistry->registerItem(
            group: 'Main',
            label: 'Dashboard',
            route: 'dashboard',
            icon: 'Home',
            order: 1,
            permission: null,
            parentKey: null,
            module: null
        );

        Event::listen(
            MigrationsEnded::class,
            AutoInstallProtectedModules::class
        );

        $storageLinkService->ensureExists();

        $appConfigService->syncFromSettings();
    }
}
