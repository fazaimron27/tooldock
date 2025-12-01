<?php

namespace App\Services\Modules;

use App\Exceptions\MissingDependencyException;
use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\SettingsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;

class ModuleLifecycleService
{
    /**
     * @param  RepositoryInterface  $moduleRepository
     * @param  ActivatorInterface  $activator
     * @param  ModuleDependencyValidator  $dependencyValidator
     * @param  ModuleDependencyChecker  $dependencyChecker
     * @param  ModulePermissionManager  $permissionManager
     * @param  ModuleRegistryHelper  $registryHelper
     * @param  ModuleDiscoveryService  $discoveryService
     * @param  ModuleMigrationService  $migrationService
     * @param  ModuleStatusService  $statusService
     * @param  SettingsService  $settingsService
     * @param  CategoryRegistry  $categoryRegistry
     *
     * Note: Sets lifecycle service in discovery service to break circular dependency.
     */
    public function __construct(
        private RepositoryInterface $moduleRepository,
        private ActivatorInterface $activator,
        private ModuleDependencyValidator $dependencyValidator,
        private ModuleDependencyChecker $dependencyChecker,
        private ModulePermissionManager $permissionManager,
        private ModuleRegistryHelper $registryHelper,
        private ModuleDiscoveryService $discoveryService,
        private ModuleMigrationService $migrationService,
        private ModuleStatusService $statusService,
        private SettingsService $settingsService,
        private CategoryRegistry $categoryRegistry
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

        $this->dependencyValidator->checkInstalled($module, checkEnabled: false, skipValidation: $skipValidation);
        Log::info("ModuleLifecycleService: Dependencies checked for '{$moduleName}'");

        $this->statusService->markAsInstalled($moduleName, $module->get('version'));
        Log::info("ModuleLifecycleService: Updated modules_statuses for '{$moduleName}'");

        $this->activator->enable($module);
        Log::info("ModuleLifecycleService: Enabled module '{$moduleName}' via activator");

        $this->migrationService->runMigrations($moduleName);

        $this->permissionManager->runSeeder($moduleName);

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

        try {
            $this->settingsService->sync();
            Log::info("ModuleLifecycleService: Synced settings after installing '{$moduleName}'");
        } catch (\Exception $e) {
            Log::debug("ModuleLifecycleService: Settings sync skipped for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->categoryRegistry->seed();
            Log::info("ModuleLifecycleService: Seeded categories after installing '{$moduleName}'");
        } catch (\Exception $e) {
            Log::debug("ModuleLifecycleService: Category seeding skipped for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info("ModuleLifecycleService: Installation complete for '{$moduleName}'");
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

        $this->dependencyChecker->checkForUninstall($moduleName);

        $this->disable($moduleName);

        $this->permissionManager->cleanup($moduleName);

        try {
            $stats = $this->settingsService->cleanup($moduleName);
            Log::info("ModuleLifecycleService: Cleaned up settings for '{$moduleName}'", [
                'deleted' => $stats['deleted'],
            ]);
        } catch (\Exception $e) {
            Log::debug("ModuleLifecycleService: Settings cleanup skipped for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $stats = $this->categoryRegistry->cleanup($moduleName);
            Log::info("ModuleLifecycleService: Cleaned up categories for '{$moduleName}'", [
                'deleted' => $stats['deleted'],
                'orphaned' => $stats['orphaned'],
            ]);
        } catch (\Exception $e) {
            Log::debug("ModuleLifecycleService: Category cleanup skipped for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }

        $this->migrationService->rollbackMigrations($moduleName);

        $this->statusService->markAsUninstalled($moduleName);
    }

    /**
     * Enable a module
     *
     * Activates a previously installed module:
     * 1. Verifies module is installed
     * 2. Validates dependencies are installed AND enabled
     * 3. Sets is_active flag in database
     * 4. Enables via activator (updates nwidart/laravel-modules cache)
     * 5. Performs cleanup (reload statuses, refresh registry, clear caches, generate routes)
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

        $this->dependencyValidator->checkInstalled($module, checkEnabled: true, skipValidation: $skipValidation);

        $this->statusService->setActive($moduleName, true);

        $this->activator->enable($module);

        try {
            $this->settingsService->sync();
            Log::info("ModuleLifecycleService: Synced settings after enabling '{$moduleName}'");
        } catch (\Exception $e) {
            Log::debug("ModuleLifecycleService: Settings sync skipped for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->categoryRegistry->seed();
            Log::info("ModuleLifecycleService: Seeded categories after enabling '{$moduleName}'");
        } catch (\Exception $e) {
            Log::debug("ModuleLifecycleService: Category seeding skipped for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }

        $this->registryHelper->finalize();
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

        $this->dependencyChecker->checkForDisable($moduleName);

        $this->statusService->setActive($moduleName, false);

        $this->activator->disable($module);

        $this->registryHelper->finalize();
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
}
