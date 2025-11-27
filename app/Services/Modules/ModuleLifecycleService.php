<?php

namespace App\Services\Modules;

use App\Exceptions\MissingDependencyException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Nwidart\Modules\Module;

class ModuleLifecycleService
{
    public function __construct(
        private RepositoryInterface $moduleRepository,
        private ActivatorInterface $activator
    ) {}

    /**
     * Check if all required dependencies are installed (and optionally enabled)
     *
     * Validates that all modules listed in module.json "requires" array are:
     * - Present in the Modules directory
     * - Installed in the database
     * - Optionally enabled (when $checkEnabled is true)
     *
     * @param  Module  $module  The module to check dependencies for
     * @param  bool  $checkEnabled  If true, also verifies dependencies are enabled (for enable operation)
     *
     * @throws MissingDependencyException When a required dependency is missing, not installed, or not enabled
     */
    private function checkDependencies(Module $module, bool $checkEnabled = false): void
    {
        $requires = $module->get('requires', []);

        if (empty($requires)) {
            return;
        }

        $operation = $checkEnabled ? 'enable' : 'install';

        foreach ($requires as $requiredModuleName) {
            if (! ModuleFacade::has($requiredModuleName)) {
                throw new MissingDependencyException(
                    "Cannot {$operation} '{$module->getName()}' because the required dependency '{$requiredModuleName}' is missing.\n".
                        "Please ensure the '{$requiredModuleName}' module exists in the Modules directory."
                );
            }

            $isInstalled = DB::table('modules_statuses')
                ->where('name', $requiredModuleName)
                ->where('is_installed', true)
                ->exists();

            if (! $isInstalled) {
                throw new MissingDependencyException(
                    "Cannot {$operation} '{$module->getName()}' because the required dependency '{$requiredModuleName}' is not installed.\n".
                        "Please install '{$requiredModuleName}' first by running:\n".
                        "  php artisan module:manage {$requiredModuleName} --action=install"
                );
            }

            if ($checkEnabled && ! ModuleFacade::isEnabled($requiredModuleName)) {
                throw new MissingDependencyException(
                    "Cannot {$operation} '{$module->getName()}' because the required dependency '{$requiredModuleName}' is not enabled.\n".
                        "Please enable '{$requiredModuleName}' first by running:\n".
                        "  php artisan module:manage {$requiredModuleName} --action=enable"
                );
            }
        }
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
     *
     * @throws MissingDependencyException When required dependencies are not installed
     */
    public function install(string $moduleName, bool $withSeed = false): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        $this->checkDependencies($module);

        // Note: is_active is NOT set here - that's done by enable() at the end
        DB::table('modules_statuses')->updateOrInsert(
            ['name' => $moduleName],
            [
                'is_installed' => true,
                'installed_at' => now(),
                'version' => $module->get('version'),
                'updated_at' => now(),
            ]
        );

        // Temporarily enable module so migrations can be discovered
        // The nwidart/laravel-modules package only discovers migrations for enabled modules
        $this->activator->enable($module);

        Artisan::call('module:migrate', [
            'module' => $moduleName,
            '--force' => true,
        ]);

        // Fallback: if module:migrate didn't run migrations, try direct path
        $output = Artisan::output();
        if (str_contains($output, 'Nothing to migrate') && ! str_contains($output, 'Migrated:')) {
            $migrationPath = $module->getPath().'/database/migrations';
            if (is_dir($migrationPath)) {
                Artisan::call('migrate', [
                    '--path' => 'Modules/'.$moduleName.'/database/migrations',
                    '--force' => true,
                ]);
            }
        }

        if ($withSeed) {
            $seedResult = Artisan::call('module:seed', [
                'module' => $moduleName,
                '--force' => true,
            ]);
            if ($seedResult !== 0) {
                throw new \RuntimeException(
                    "Failed to run seeders for module '{$moduleName}'.\n".
                        "You can try installing without seeders:\n".
                        "  php artisan module:manage {$moduleName} --action=install"
                );
            }
        }

        // Enable module (sets is_active and performs cleanup)
        $this->enable($moduleName);
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

        $this->checkReverseDependencies($moduleName);

        // Disable module first (so routes are deactivated before rollback)
        $this->disable($moduleName);

        Artisan::call('module:migrate-rollback', [
            'module' => $moduleName,
            '--force' => true,
        ]);

        DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->update([
                'is_installed' => false,
                'updated_at' => now(),
            ]);
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
     *
     * @throws MissingDependencyException When required dependencies are not installed or enabled
     * @throws \RuntimeException When module is not installed
     */
    public function enable(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        $isInstalled = DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->where('is_installed', true)
            ->exists();

        if (! $isInstalled) {
            throw new \RuntimeException(
                "Cannot enable '{$moduleName}' because it is not installed.\n".
                    "Please install '{$moduleName}' first by running:\n".
                    "  php artisan module:manage {$moduleName} --action=install"
            );
        }

        $this->checkDependencies($module, checkEnabled: true);

        DB::table('modules_statuses')->updateOrInsert(
            ['name' => $moduleName],
            [
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        // Enable via activator (updates nwidart/laravel-modules cache)
        // Makes module discoverable by ModuleFacade::isEnabled() and loads routes/providers
        $this->activator->enable($module);

        $this->finalizeModuleOperation();
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

        $this->checkReverseDependenciesForDisable($moduleName);

        DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        // Disable via activator (updates nwidart/laravel-modules cache)
        // Makes module undiscoverable and unloads routes/providers
        $this->activator->disable($module);

        $this->finalizeModuleOperation();
    }

    /**
     * Check if other active modules depend on this module (for disable operation)
     *
     * Prevents disabling a module that is required by currently active modules.
     * Only checks enabled modules since disabled modules don't need their dependencies active.
     *
     * @param  string  $moduleName  The module to check reverse dependencies for
     *
     * @throws \RuntimeException When active modules depend on this module
     */
    private function checkReverseDependenciesForDisable(string $moduleName): void
    {
        $dependents = [];

        foreach (ModuleFacade::allEnabled() as $activeModule) {
            $activeModuleName = $activeModule->getName();

            if ($activeModuleName === $moduleName) {
                continue;
            }

            $requires = $activeModule->get('requires', []);

            if (in_array($moduleName, $requires, true)) {
                $dependents[] = $activeModuleName;
            }
        }

        if (! empty($dependents)) {
            $dependentsList = implode("', '", $dependents);
            $firstDependent = $dependents[0];
            throw new \RuntimeException(
                "Cannot disable '{$moduleName}' because the following active modules depend on it: '{$dependentsList}'.\n".
                    "Please disable the dependent modules first:\n".
                    "  php artisan module:manage {$firstDependent} --action=disable"
            );
        }
    }

    /**
     * Check if other installed modules depend on this module (for uninstall operation)
     *
     * Prevents uninstalling a module that is required by other installed modules.
     * Checks ALL installed modules (not just enabled) since installed modules may be enabled later.
     *
     * @param  string  $moduleName  The module to check reverse dependencies for
     *
     * @throws \RuntimeException When installed modules depend on this module
     */
    private function checkReverseDependencies(string $moduleName): void
    {
        // Check all installed modules (not just enabled) because:
        // - Disabled modules may be re-enabled later and need their dependencies
        // - Uninstalling a dependency would permanently break dependent modules
        $installedModules = DB::table('modules_statuses')
            ->where('is_installed', true)
            ->pluck('name')
            ->toArray();

        $dependents = [];

        foreach ($installedModules as $installedModuleName) {
            if ($installedModuleName === $moduleName) {
                continue;
            }

            $installedModule = ModuleFacade::find($installedModuleName);

            // Skip orphaned database records (module files deleted)
            if ($installedModule === null) {
                continue;
            }

            $requires = $installedModule->get('requires', []);

            if (in_array($moduleName, $requires, true)) {
                $dependents[] = $installedModuleName;
            }
        }

        if (! empty($dependents)) {
            $dependentsList = implode("', '", $dependents);
            $firstDependent = $dependents[0];
            throw new \RuntimeException(
                "Cannot uninstall '{$moduleName}' because the following installed modules depend on it: '{$dependentsList}'.\n".
                    "Please uninstall the dependent modules first:\n".
                    "  php artisan module:manage {$firstDependent} --action=uninstall"
            );
        }
    }

    /**
     * Reload statuses if the activator is a DatabaseActivator
     *
     * DatabaseActivator caches module statuses in memory. After external database changes
     * (like direct SQL updates), we need to reload the cache to keep it in sync.
     *
     * This is a no-op for other activator types (e.g., FileActivator).
     */
    private function reloadStatusesIfNeeded(): void
    {
        if ($this->activator instanceof DatabaseActivator) {
            $this->activator->reloadStatuses();
        }
    }

    /**
     * Refresh the module registry by scanning, registering, and booting modules
     *
     * Ensures the nwidart/laravel-modules package discovers any new or changed modules
     * after installation/enabling. This is necessary because:
     * - scan() discovers modules in the filesystem
     * - register() registers service providers
     * - boot() boots registered providers
     */
    private function refreshModuleRegistry(): void
    {
        ModuleFacade::scan();
        ModuleFacade::register();
        ModuleFacade::boot();
    }

    /**
     * Clear application caches (config and routes)
     *
     * Clears Laravel's cached configuration and routes to ensure changes from
     * newly installed/enabled modules are picked up immediately.
     */
    private function clearApplicationCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
    }

    /**
     * Finalize a module operation by performing all cleanup steps
     *
     * Centralized cleanup method called after enable/disable operations.
     * Ensures consistency by:
     * 1. Reloading activator statuses (if DatabaseActivator)
     * 2. Refreshing module registry (discover new modules)
     * 3. Clearing caches (config, routes)
     * 4. Regenerating Ziggy routes (for frontend route helpers)
     */
    private function finalizeModuleOperation(): void
    {
        $this->reloadStatusesIfNeeded();
        $this->refreshModuleRegistry();
        $this->clearApplicationCaches();
        $this->generateZiggyRoutes();
    }

    /**
     * Generate Ziggy routes using the default Artisan command
     *
     * Regenerates the Ziggy route definitions file (resources/js/ziggy.js) to include
     * routes from newly installed/enabled modules. This allows the frontend to use
     * route() helper functions for module routes.
     */
    private function generateZiggyRoutes(): void
    {
        Artisan::call('ziggy:generate');
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
        ModuleFacade::scan();

        $allModules = ModuleFacade::all();
        $discoveredModules = [];

        foreach ($allModules as $module) {
            $moduleName = $module->getName();

            // Preserve existing installation status if module already registered
            $exists = DB::table('modules_statuses')
                ->where('name', $moduleName)
                ->exists();

            if (! $exists) {
                DB::table('modules_statuses')->insert([
                    'name' => $moduleName,
                    'is_active' => false,
                    'is_installed' => false,
                    'version' => $module->get('version'),
                    'installed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Update version if changed
                DB::table('modules_statuses')
                    ->where('name', $moduleName)
                    ->update([
                        'version' => $module->get('version'),
                        'updated_at' => now(),
                    ]);
            }

            $discoveredModules[] = $moduleName;
        }

        $this->reloadStatusesIfNeeded();

        return $discoveredModules;
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
        $this->discoverAndRegisterAll();

        $allModules = ModuleFacade::all();
        $protectedModules = [];

        foreach ($allModules as $module) {
            if ($module->get('protected') === true) {
                $protectedModules[] = $module;
            }
        }

        if (empty($protectedModules)) {
            return [];
        }

        // Sort by dependencies (modules with no dependencies first)
        usort($protectedModules, function (Module $a, Module $b) {
            $aRequires = $a->get('requires', []);
            $bRequires = $b->get('requires', []);

            if (in_array($b->getName(), $aRequires, true)) {
                return 1;
            }

            if (in_array($a->getName(), $bRequires, true)) {
                return -1;
            }

            return 0;
        });

        $installedModules = [];

        foreach ($protectedModules as $module) {
            $moduleName = $module->getName();

            $isInstalled = DB::table('modules_statuses')
                ->where('name', $moduleName)
                ->where('is_installed', true)
                ->exists();

            if ($isInstalled) {
                continue;
            }

            try {
                $this->install($moduleName, withSeed: false);
                $installedModules[] = $moduleName;
            } catch (\Exception $e) {
                // Continue with other modules even if one fails
                \Illuminate\Support\Facades\Log::error(
                    "Failed to auto-install protected module '{$moduleName}'",
                    [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }
        }

        return $installedModules;
    }
}
