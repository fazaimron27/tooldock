<?php

namespace App\Services\Modules;

use Nwidart\Modules\Facades\Module as ModuleFacade;

class ModuleDependencyChecker
{
    public function __construct(
        private ModuleStatusService $statusService
    ) {}

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
    public function checkForDisable(string $moduleName): void
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
            $count = count($dependents);

            if ($count === 1) {
                $message = "Cannot disable '{$moduleName}' because the active module '{$dependents[0]}' depends on it. Please disable '{$dependents[0]}' first.";
            } else {
                $dependentsList = implode("', '", array_slice($dependents, 0, -1))."' and '".end($dependents);
                $message = "Cannot disable '{$moduleName}' because the following active modules depend on it: '{$dependentsList}'. Please disable these modules first.";
            }

            throw new \RuntimeException($message);
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
    public function checkForUninstall(string $moduleName): void
    {
        $installedModules = $this->statusService->getInstalledModuleNames();

        $dependents = [];

        foreach ($installedModules as $installedModuleName) {
            if ($installedModuleName === $moduleName) {
                continue;
            }

            $installedModule = ModuleFacade::find($installedModuleName);

            if ($installedModule === null) {
                continue;
            }

            $requires = $installedModule->get('requires', []);

            if (in_array($moduleName, $requires, true)) {
                $dependents[] = $installedModuleName;
            }
        }

        if (! empty($dependents)) {
            $count = count($dependents);

            if ($count === 1) {
                $message = "Cannot uninstall '{$moduleName}' because the installed module '{$dependents[0]}' depends on it. Please uninstall '{$dependents[0]}' first.";
            } else {
                $dependentsList = implode("', '", array_slice($dependents, 0, -1))."' and '".end($dependents);
                $message = "Cannot uninstall '{$moduleName}' because the following installed modules depend on it: '{$dependentsList}'. Please uninstall these modules first.";
            }

            throw new \RuntimeException($message);
        }
    }
}
