<?php

/**
 * Application Service Provider
 *
 * Registers core application services, bootstraps Vite
 * configuration, module auto-installation, and event
 * listeners for module lifecycle events.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Providers;

use App\Events\Modules\ModuleDisabled;
use App\Events\Modules\ModuleEnabled;
use App\Events\Modules\ModuleInstalled;
use App\Events\Modules\ModuleInstalling;
use App\Events\Modules\ModuleUninstalled;
use App\Events\Modules\ModuleUninstalling;
use App\Listeners\AutoInstallProtectedModules;
use App\Listeners\NotifyAdminsOnModuleChange;
use App\Services\Cache\CacheMetricsService;
use App\Services\Cache\CacheService;
use App\Services\Core\AppConfigService;
use App\Services\Core\ExceptionResponseService;
use App\Services\Core\InertiaSharedDataService;
use App\Services\Core\SettingsService;
use App\Services\Core\StorageLinkService;
use App\Services\Media\MediaConfigService;
use App\Services\Modules\ProtectedModuleMigrationService;
use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\CommandRegistry;
use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\GroupRegistry;
use App\Services\Registry\InertiaSharedDataRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\MiddlewareRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\RoleRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\SignalCategoryRegistry;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;
use Modules\Core\Services\SuperAdminService;

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
        $this->app->singleton(CacheService::class);
        $this->app->singleton(CacheMetricsService::class);
        $this->app->singleton(DashboardWidgetRegistry::class);
        $this->app->singleton(MenuRegistry::class);
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(CategoryRegistry::class);
        $this->app->singleton(CommandRegistry::class);
        $this->app->singleton(RoleRegistry::class);
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(GroupRegistry::class);
        $this->app->singleton(InertiaSharedDataRegistry::class);
        $this->app->singleton(MiddlewareRegistry::class);
        $this->app->singleton(SignalCategoryRegistry::class);
        $this->app->singleton(SignalHandlerRegistry::class);
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
        MiddlewareRegistry $middlewareRegistry,
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

        $this->app->booted(function () use ($middlewareRegistry) {
            $moduleMiddleware = $middlewareRegistry->getAll();

            if (! empty($moduleMiddleware)) {
                $router = $this->app->make(Router::class);
                foreach ($moduleMiddleware as $middleware) {
                    $router->pushMiddlewareToGroup('web', $middleware);
                }
            }
        });

        Event::listen(
            MigrationsEnded::class,
            AutoInstallProtectedModules::class
        );
        Event::listen(
            ModuleInstalling::class,
            [NotifyAdminsOnModuleChange::class, 'handleInstalling']
        );
        Event::listen(
            ModuleUninstalling::class,
            [NotifyAdminsOnModuleChange::class, 'handleUninstalling']
        );
        Event::listen(
            ModuleInstalled::class,
            [NotifyAdminsOnModuleChange::class, 'handleInstalled']
        );
        Event::listen(
            ModuleUninstalled::class,
            [NotifyAdminsOnModuleChange::class, 'handleUninstalled']
        );
        Event::listen(
            ModuleEnabled::class,
            [NotifyAdminsOnModuleChange::class, 'handleEnabled']
        );
        Event::listen(
            ModuleDisabled::class,
            [NotifyAdminsOnModuleChange::class, 'handleDisabled']
        );

        $storageLinkService->ensureExists();

        $appConfigService->syncFromSettings();

        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole(Roles::SUPER_ADMIN);
        });
    }
}
