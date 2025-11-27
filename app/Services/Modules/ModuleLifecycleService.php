<?php

namespace App\Services\Modules;

use App\Exceptions\MissingDependencyException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

class ModuleLifecycleService
{
    public function __construct(
        private RepositoryInterface $moduleRepository,
        private ActivatorInterface $activator
    ) {}

    /**
     * Check if all required dependencies are installed
     *
     * @throws MissingDependencyException
     */
    private function checkDependencies(Module $module): void
    {
        $requires = $module->get('requires', []);

        if (empty($requires)) {
            return;
        }

        foreach ($requires as $requiredModuleName) {
            // Check if the required module exists
            $requiredModule = $this->moduleRepository->find($requiredModuleName);

            if ($requiredModule === null) {
                throw new MissingDependencyException(
                    "Cannot install {$module->getName()} because dependency '{$requiredModuleName}' is missing."
                );
            }

            // Check if the required module is installed in the database
            $isInstalled = DB::table('modules_statuses')
                ->where('name', $requiredModuleName)
                ->where('is_installed', true)
                ->exists();

            if (! $isInstalled) {
                throw new MissingDependencyException(
                    "Cannot install {$module->getName()} because dependency '{$requiredModuleName}' is not installed."
                );
            }
        }
    }

    /**
     * Get module metadata for frontend display
     *
     * @return array{icon: string|null, version: string|null, description: string|null}
     */
    public function getMetadata(Module $module): array
    {
        return [
            'icon' => $module->get('icon'),
            'version' => $module->get('version'),
            'description' => $module->get('description'),
        ];
    }

    /**
     * Install a module
     *
     * @throws MissingDependencyException
     */
    public function install(string $moduleName, bool $withSeed = false): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        // Check dependencies
        $this->checkDependencies($module);

        // Enable module first (required for migrations to be discovered)
        $this->activator->enable($module);

        // Run migrations using module:migrate command
        // Use --force flag to run in non-interactive mode
        $migrateResult = Artisan::call('module:migrate', [
            'module' => $moduleName,
            '--force' => true,
        ]);

        // Check if migrations actually ran by checking the output
        $output = Artisan::output();
        if (str_contains($output, 'Nothing to migrate') && ! str_contains($output, 'Migrated:')) {
            // If nothing migrated, try running migrations directly
            $migrationPath = $module->getPath().'/database/migrations';
            if (is_dir($migrationPath)) {
                Artisan::call('migrate', [
                    '--path' => 'Modules/'.$moduleName.'/database/migrations',
                    '--force' => true,
                ]);
            }
        }

        // Run seeders only if --seed flag is provided
        if ($withSeed) {
            $seedResult = Artisan::call('module:seed', [
                'module' => $moduleName,
                '--force' => true,
            ]);
            if ($seedResult !== 0) {
                throw new \RuntimeException("Failed to run seeders for module {$moduleName}");
            }
        }

        // Update database
        DB::table('modules_statuses')->updateOrInsert(
            ['name' => $moduleName],
            [
                'is_installed' => true,
                'is_active' => true,
                'installed_at' => now(),
                'version' => $module->get('version'),
                'updated_at' => now(),
            ]
        );

        // Regenerate Ziggy routes to include new module routes
        Artisan::call('ziggy:generate');
    }

    /**
     * Uninstall a module
     *
     * @throws \RuntimeException
     */
    public function uninstall(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        // Check reverse dependencies
        $this->checkReverseDependencies($moduleName);

        // Rollback migrations
        Artisan::call('module:migrate-rollback', ['module' => $moduleName]);

        // Update database
        DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->update([
                'is_installed' => false,
                'is_active' => false,
                'updated_at' => now(),
            ]);

        // Disable via activator
        $this->activator->disable($module);

        // Regenerate Ziggy routes to remove uninstalled module routes
        Artisan::call('ziggy:generate');
    }

    /**
     * Enable a module
     */
    public function enable(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        // Update database first
        DB::table('modules_statuses')->updateOrInsert(
            ['name' => $moduleName],
            [
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        // Enable via activator (this will also update the cache)
        $this->activator->enable($module);

        // Reload statuses to ensure sync (if DatabaseActivator)
        if ($this->activator instanceof DatabaseActivator) {
            $this->activator->reloadStatuses();
        }

        // Regenerate Ziggy routes when module is enabled (routes become active)
        Artisan::call('ziggy:generate');
    }

    /**
     * Disable a module
     */
    public function disable(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        // Update database
        DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        // Disable via activator
        $this->activator->disable($module);

        // Regenerate Ziggy routes when module is disabled (routes become inactive)
        Artisan::call('ziggy:generate');
    }

    /**
     * Check if other active/installed modules depend on this module
     *
     * @throws \RuntimeException
     */
    private function checkReverseDependencies(string $moduleName): void
    {
        // Get all active/installed modules from database
        $installedModules = DB::table('modules_statuses')
            ->where('is_installed', true)
            ->pluck('name')
            ->toArray();

        $dependents = [];

        foreach ($installedModules as $installedModuleName) {
            if ($installedModuleName === $moduleName) {
                continue;
            }

            $installedModule = $this->moduleRepository->find($installedModuleName);

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
            throw new \RuntimeException(
                "Cannot uninstall {$moduleName} because the following active modules depend on it: '{$dependentsList}'"
            );
        }
    }
}
