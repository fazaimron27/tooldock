<?php

namespace App\Services\Modules;

use App\Events\Modules\ModuleDisabled;
use App\Events\Modules\ModuleDisabling;
use App\Events\Modules\ModuleEnabled;
use App\Events\Modules\ModuleEnabling;
use App\Events\Modules\ModuleInstalled;
use App\Events\Modules\ModuleInstalling;
use App\Events\Modules\ModuleUninstalled;
use App\Events\Modules\ModuleUninstalling;
use App\Exceptions\MissingDependencyException;
use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\RoleRegistry;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Facades\Module as ModuleFacade;

class ModuleLifecycleService
{
    /**
     * @param  RepositoryInterface  $moduleRepository
     * @param  ActivatorInterface  $activator
     * @param  ModuleDependencyValidator  $dependencyValidator
     * @param  ModuleDependencyChecker  $dependencyChecker
     * @param  ModuleRegistryHelper  $registryHelper
     * @param  ModuleDiscoveryService  $discoveryService
     * @param  ModuleMigrationService  $migrationService
     * @param  ModuleStatusService  $statusService
     * @param  SettingsRegistry  $settingsRegistry
     * @param  CategoryRegistry  $categoryRegistry
     * @param  MenuRegistry  $menuRegistry
     * @param  RoleRegistry  $roleRegistry
     * @param  PermissionRegistry  $permissionRegistry
     */
    public function __construct(
        private RepositoryInterface $moduleRepository,
        private ActivatorInterface $activator,
        private ModuleDependencyValidator $dependencyValidator,
        private ModuleDependencyChecker $dependencyChecker,
        private ModuleRegistryHelper $registryHelper,
        private ModuleDiscoveryService $discoveryService,
        private ModuleMigrationService $migrationService,
        private ModuleStatusService $statusService,
        private SettingsRegistry $settingsRegistry,
        private CategoryRegistry $categoryRegistry,
        private MenuRegistry $menuRegistry,
        private RoleRegistry $roleRegistry,
        private PermissionRegistry $permissionRegistry
    ) {
        $this->discoveryService->setLifecycleService($this);
    }

    /**
     * Install a module
     *
     * Performs the complete installation process:
     * 1. Validates dependencies are installed
     * 2. Records installation in database (is_installed, version, installed_at)
     * 3. Temporarily enables module (so migrations can be discovered)
     * 4. Runs database migrations
     * 5. Optionally runs seeders
     * 6. Calls enable() to activate module and perform cleanup
     *
     * @param  string  $moduleName  The name of the module to install
     * @param  bool  $withSeed  If true, run module seeders after migrations
     * @param  bool  $skipValidation  If true, skip dependency validation (useful for CI/CD or trusted modules)
     *
     * @throws MissingDependencyException When required dependencies are not installed
     */
    public function install(string $moduleName, bool $withSeed = false, bool $skipValidation = false): void
    {
        Log::info("ModuleLifecycleService: Starting install for module '{$moduleName}'");

        $module = $this->moduleRepository->findOrFail($moduleName);
        Log::info("ModuleLifecycleService: Found module '{$moduleName}'", [
            'path' => $module->getPath(),
            'version' => $module->get('version'),
        ]);

        $event = new ModuleInstalling($module, $moduleName, $withSeed, $skipValidation);
        Event::dispatch($event);
        if ($event->preventInstall) {
            throw new \RuntimeException($event->preventionReason ?? 'Installation prevented by event listener');
        }

        $this->dependencyValidator->checkInstalled($module, checkEnabled: false, skipValidation: $skipValidation);
        Log::info("ModuleLifecycleService: Dependencies checked for '{$moduleName}'");

        if (! $skipValidation) {
            $this->registryHelper->reloadStatuses();

            $validatedDependencies = $this->dependencyValidator->validate($module, $skipValidation);
            foreach ($validatedDependencies as $requiredModuleName) {
                if ($this->statusService->isInstalled($requiredModuleName) && ! ModuleFacade::isEnabled($requiredModuleName)) {
                    throw new MissingDependencyException(
                        "Cannot install '{$moduleName}' because the required dependency '{$requiredModuleName}' is installed but not enabled.\n".
                            "Please enable '{$requiredModuleName}' first."
                    );
                }
            }
        }

        $this->statusService->markAsInstalled($moduleName, $module->get('version'));
        Log::info("ModuleLifecycleService: Updated modules_statuses for '{$moduleName}'");

        $this->activator->enable($module);
        Log::info("ModuleLifecycleService: Enabled module '{$moduleName}' via activator");

        $this->migrationService->runMigrations($moduleName);

        if ($withSeed) {
            Log::info("ModuleLifecycleService: Running database seeders for '{$moduleName}'");
            $seedResult = Artisan::call('module:seed', [
                'module' => $moduleName,
                '--force' => true,
            ]);
            if ($seedResult !== 0) {
                Log::warning("ModuleLifecycleService: Database seeder failed for '{$moduleName}'", [
                    'error' => 'This is optional and only creates sample data.',
                ]);
            } else {
                Log::info("ModuleLifecycleService: Database seeders completed for '{$moduleName}'");
            }
        }

        $this->enable($moduleName, skipValidation: $skipValidation);

        Log::info("ModuleLifecycleService: Installation complete for '{$moduleName}'");

        $this->dispatchAfterEvent(new ModuleInstalled($module, $moduleName, $withSeed));
    }

    /**
     * Uninstall a module
     *
     * Performs the complete uninstallation process:
     * 1. Validates no installed modules depend on this module
     * 2. Disables module (deactivates routes and services)
     * 3. Rolls back database migrations
     * 4. Marks module as uninstalled in database
     *
     * Note: This does NOT delete the module files, only removes it from the system.
     *
     * @param  string  $moduleName  The name of the module to uninstall
     *
     * @throws \RuntimeException When other installed modules depend on this module
     */
    public function uninstall(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        if ($module->get('protected') === true) {
            throw new \RuntimeException(
                "Cannot uninstall '{$moduleName}' because it is a protected module.\n".
                    'Protected modules are essential to the system and cannot be removed.'
            );
        }

        $event = new ModuleUninstalling($module, $moduleName);
        Event::dispatch($event);
        if ($event->preventUninstall) {
            throw new \RuntimeException($event->preventionReason ?? 'Uninstallation prevented by event listener');
        }

        $this->dependencyChecker->checkForUninstall($moduleName);

        $this->disable($moduleName);

        $this->cleanupAllRegistries($moduleName);

        $this->migrationService->rollbackMigrations($moduleName);

        $this->statusService->markAsUninstalled($moduleName);

        $this->dispatchAfterEvent(new ModuleUninstalled($module, $moduleName));
    }

    /**
     * Enable a module
     *
     * Activates a previously installed module:
     * 1. Verifies module is installed
     * 2. Validates dependencies are installed AND enabled
     * 3. Sets is_active flag in database
     * 4. Enables via activator (updates nwidart/laravel-modules cache)
     * 5. Refreshes module registry to boot service providers (allows registries to register data)
     * 6. Seeds all registries (settings, roles, categories, permissions)
     * 7. Performs cleanup (reload statuses, refresh registry, clear caches, generate routes)
     *
     * Called by install() after migrations/seeders, or independently to re-enable a disabled module.
     *
     * @param  string  $moduleName  The name of the module to enable
     * @param  bool  $skipValidation  If true, skip dependency validation (useful for CI/CD or trusted modules)
     *
     * @throws MissingDependencyException When required dependencies are not installed or enabled
     * @throws \RuntimeException When module is not installed
     */
    public function enable(string $moduleName, bool $skipValidation = false): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        if (! $this->statusService->isInstalled($moduleName)) {
            throw new \RuntimeException(
                "Cannot enable '{$moduleName}' because it is not installed.\n".
                    "Please install '{$moduleName}' first."
            );
        }

        $event = new ModuleEnabling($module, $moduleName, $skipValidation);
        Event::dispatch($event);
        if ($event->preventEnable) {
            throw new \RuntimeException($event->preventionReason ?? 'Enabling prevented by event listener');
        }

        $this->dependencyValidator->checkInstalled($module, checkEnabled: true, skipValidation: $skipValidation);

        $this->statusService->setActive($moduleName, true);

        $this->activator->enable($module);

        $this->registryHelper->refresh();

        $this->seedAllRegistries($moduleName);

        $this->registryHelper->finalize();

        $this->dispatchAfterEvent(new ModuleEnabled($module, $moduleName));
    }

    /**
     * Disable a module
     *
     * Deactivates an enabled module:
     * 1. Validates no active modules depend on this module
     * 2. Sets is_active flag to false in database
     * 3. Disables via activator (updates nwidart/laravel-modules cache)
     * 4. Performs cleanup (reload statuses, refresh registry, clear caches, generate routes)
     *
     * Called by uninstall() before rollback, or independently to temporarily disable a module.
     *
     * @param  string  $moduleName  The name of the module to disable
     *
     * @throws \RuntimeException When active modules depend on this module
     */
    public function disable(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        if ($module->get('protected') === true) {
            throw new \RuntimeException(
                "Cannot disable '{$moduleName}' because it is a protected module.\n".
                    'Protected modules are essential to the system and must remain enabled.'
            );
        }

        $event = new ModuleDisabling($module, $moduleName);
        Event::dispatch($event);
        if ($event->preventDisable) {
            throw new \RuntimeException($event->preventionReason ?? 'Disabling prevented by event listener');
        }

        $this->dependencyChecker->checkForDisable($moduleName);

        $this->statusService->setActive($moduleName, false);

        $this->activator->disable($module);

        $this->registryHelper->finalize();

        $this->dispatchAfterEvent(new ModuleDisabled($module, $moduleName));
    }

    /**
     * Discover and register all available modules in the database
     *
     * Scans the Modules directory for all available modules and registers them
     * in the modules_statuses table. This is useful after a fresh database migration
     * to ensure all modules are tracked in the database.
     *
     * Modules are registered with is_installed=false and is_active=false by default.
     * They must be explicitly installed using install() or module:manage command.
     *
     * @return array<string> Array of discovered module names
     */
    public function discoverAndRegisterAll(): array
    {
        return $this->discoveryService->discoverAndRegisterAll();
    }

    /**
     * Discover and install all protected modules automatically.
     *
     * This method is called after migrations complete on a fresh database
     * to automatically install essential protected modules (like Core).
     * Only modules marked as "protected": true in their module.json are installed.
     *
     * Modules are installed in dependency order (modules with no dependencies first).
     *
     * @return array<string> Array of installed module names
     */
    public function installProtectedModules(): array
    {
        return $this->discoveryService->installProtectedModules();
    }

    /**
     * Seed all registries for a module.
     *
     * Centralized method to seed all registries (settings, roles, categories, permissions)
     * with error handling. Uses fail-open strategy - continues on errors to prevent
     * one registry failure from blocking the entire operation.
     *
     * @param  string  $moduleName  The name of the module
     */
    private function seedAllRegistries(string $moduleName): void
    {
        try {
            $this->settingsRegistry->seed();
            Log::info("ModuleLifecycleService: Seeded settings for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Settings seeding failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $this->roleRegistry->seed();
            Log::info("ModuleLifecycleService: Seeded roles for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Role seeding failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $this->categoryRegistry->seed();
            Log::info("ModuleLifecycleService: Seeded categories for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Category seeding failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $this->menuRegistry->seed();
            Log::info("ModuleLifecycleService: Seeded menus for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Menu seeding failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $this->permissionRegistry->seed();
            Log::info("ModuleLifecycleService: Seeded permissions for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Permission seeding failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $this->dependencyValidator->clearCache();
            Log::debug("ModuleLifecycleService: Cleared module dependency cache after seeding '{$moduleName}'");
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Failed to clear module dependency cache for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cleanup all registries for a module.
     *
     * Centralized method to cleanup all registries (permissions, roles, settings, categories)
     * with error handling. Uses fail-open strategy - continues on errors to prevent
     * one registry failure from blocking the entire operation.
     *
     * @param  string  $moduleName  The name of the module
     */
    private function cleanupAllRegistries(string $moduleName): void
    {
        try {
            $stats = $this->permissionRegistry->cleanup($moduleName);
            Log::info("ModuleLifecycleService: Cleaned up permissions for '{$moduleName}'", [
                'deleted' => $stats['deleted'],
                'roles_cleaned' => $stats['roles_cleaned'],
                'models_cleaned' => $stats['models_cleaned'],
            ]);
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Permission cleanup failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $stats = $this->roleRegistry->cleanup($moduleName);
            Log::info("ModuleLifecycleService: Cleaned up roles for '{$moduleName}'", [
                'deleted' => $stats['deleted'],
                'skipped' => $stats['skipped'],
            ]);
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Role cleanup failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $stats = $this->settingsRegistry->cleanup($moduleName);
            Log::info("ModuleLifecycleService: Cleaned up settings for '{$moduleName}'", [
                'deleted' => $stats['deleted'],
            ]);
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Settings cleanup failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $stats = $this->categoryRegistry->cleanup($moduleName);
            Log::info("ModuleLifecycleService: Cleaned up categories for '{$moduleName}'", [
                'deleted' => $stats['deleted'],
                'orphaned' => $stats['orphaned'],
            ]);
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Category cleanup failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $stats = $this->menuRegistry->cleanup($moduleName);
            Log::info("ModuleLifecycleService: Cleaned up menus for '{$moduleName}'", [
                'deleted' => $stats['deleted'],
                'orphaned' => $stats['orphaned'],
            ]);
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Menu cleanup failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        try {
            $this->dependencyValidator->clearCache();
            Log::debug("ModuleLifecycleService: Cleared module dependency cache after cleanup of '{$moduleName}'");
        } catch (\Exception $e) {
            Log::warning("ModuleLifecycleService: Failed to clear module dependency cache for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch an event, deferring it if running in HTTP context.
     *
     * For CLI commands, events are dispatched synchronously for better debugging
     * and error handling. For HTTP requests, "after" events are deferred to
     * improve response times by executing listeners after the response is sent.
     *
     * @param  object  $event  The event instance to dispatch
     */
    private function dispatchAfterEvent(object $event): void
    {
        if (app()->runningInConsole()) {
            Event::dispatch($event);
        } else {
            Event::defer(function () use ($event) {
                Event::dispatch($event);
            });
        }
    }
}
