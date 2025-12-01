<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Nwidart\Modules\Module;

class ModuleDiscoveryService
{
    private ?ModuleLifecycleService $lifecycleService = null;

    public function __construct(
        private ModuleRegistryHelper $registryHelper,
        private ModuleStatusService $statusService
    ) {}

    /**
     * Set the lifecycle service to break circular dependency
     *
     * This method is called after construction to inject the ModuleLifecycleService,
     * avoiding a circular dependency in the constructor.
     *
     * @param  ModuleLifecycleService  $lifecycleService  The lifecycle service instance
     */
    public function setLifecycleService(ModuleLifecycleService $lifecycleService): void
    {
        $this->lifecycleService = $lifecycleService;
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
            $version = $module->get('version');

            if (! $this->statusService->exists($moduleName)) {
                $this->statusService->register($moduleName, $version);
            } else {
                $this->statusService->updateVersion($moduleName, $version);
            }

            $discoveredModules[] = $moduleName;
        }

        $this->registryHelper->reloadStatuses();

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
        Log::info('ModuleDiscoveryService: Starting installProtectedModules');

        $this->discoverAndRegisterAll();

        $allModules = ModuleFacade::all();
        Log::info('ModuleDiscoveryService: Discovered modules', [
            'count' => count($allModules),
            'names' => array_map(fn ($m) => $m->getName(), $allModules),
        ]);

        $protectedModules = [];

        foreach ($allModules as $module) {
            if ($module->get('protected') === true) {
                $protectedModules[] = $module;
                Log::info('ModuleDiscoveryService: Found protected module', [
                    'name' => $module->getName(),
                    'version' => $module->get('version'),
                ]);
            }
        }

        if (empty($protectedModules)) {
            Log::warning('ModuleDiscoveryService: No protected modules found');

            return [];
        }

        Log::info('ModuleDiscoveryService: Sorting protected modules by dependencies');
        usort($protectedModules, function (Module $a, Module $b) {
            $aRequires = $a->get('requires', []);
            $bRequires = $b->get('requires', []);

            if (in_array($b->getName(), $aRequires, true)) {
                return 1;
            }

            if (in_array($a->getName(), $bRequires, true)) {
                return -1;
            }

            if (count($aRequires) > count($bRequires)) {
                return 1;
            }

            if (count($bRequires) > count($aRequires)) {
                return -1;
            }

            return 0;
        });

        $installedModules = [];

        $maxAttempts = count($protectedModules) * 2;
        $attempt = 0;

        while (count($installedModules) < count($protectedModules) && $attempt < $maxAttempts) {
            $attempt++;
            $progressMade = false;

            $this->statusService->reloadCache();

            foreach ($protectedModules as $module) {
                $moduleName = $module->getName();

                if (in_array($moduleName, $installedModules, true)) {
                    continue;
                }

                Log::info("ModuleDiscoveryService: Processing module '{$moduleName}'");

                $isInstalled = $this->statusService->isInstalled($moduleName);

                Log::info("ModuleDiscoveryService: Module '{$moduleName}' installation status", [
                    'isInstalled' => $isInstalled,
                ]);

                if ($isInstalled) {
                    Log::info("ModuleDiscoveryService: Skipping '{$moduleName}' - already installed");
                    $installedModules[] = $moduleName;
                    $progressMade = true;

                    continue;
                }

                $requires = $module->get('requires', []);
                $allDependenciesInstalled = true;
                $missingDependencies = [];

                foreach ($requires as $dependency) {
                    if (! $this->statusService->isInstalled($dependency)) {
                        $allDependenciesInstalled = false;
                        $missingDependencies[] = $dependency;
                    }
                }

                if (! $allDependenciesInstalled) {
                    Log::info("ModuleDiscoveryService: Module '{$moduleName}' waiting for dependencies: ".implode(', ', $missingDependencies));

                    continue;
                }

                if ($this->lifecycleService === null) {
                    Log::error("ModuleDiscoveryService: Cannot install module '{$moduleName}' - lifecycle service not set");

                    continue;
                }

                try {
                    Log::info("ModuleDiscoveryService: Installing module '{$moduleName}'");
                    $this->lifecycleService->install($moduleName, withSeed: false, skipValidation: true);
                    $installedModules[] = $moduleName;
                    $progressMade = true;
                    $this->statusService->reloadCache();
                    Log::info("ModuleDiscoveryService: Successfully installed module '{$moduleName}'");
                } catch (\Exception $e) {
                    Log::error(
                        "Failed to auto-install protected module '{$moduleName}'",
                        [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]
                    );
                    $this->statusService->reloadCache();
                }
            }

            if (! $progressMade) {
                $remainingModules = array_filter($protectedModules, function ($module) use ($installedModules) {
                    return ! in_array($module->getName(), $installedModules, true);
                });
                $remainingNames = array_map(fn ($m) => $m->getName(), $remainingModules);
                Log::warning('ModuleDiscoveryService: No progress made in dependency resolution, breaking', [
                    'remaining_modules' => $remainingNames,
                    'attempt' => $attempt,
                ]);
                break;
            }
        }

        Log::info('ModuleDiscoveryService: installProtectedModules complete', [
            'installed' => $installedModules,
            'count' => count($installedModules),
        ]);

        return $installedModules;
    }
}
