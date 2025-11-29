<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Contracts\RepositoryInterface;

class ModuleMigrationService
{
    public function __construct(
        private RepositoryInterface $moduleRepository
    ) {}

    /**
     * Run migrations for a module
     *
     * Executes both the module-specific migrate command and the standard Laravel migrate
     * command to ensure all migrations are properly applied.
     *
     * @param  string  $moduleName  The name of the module
     */
    public function runMigrations(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);
        $migrationPath = $module->getPath().'/database/migrations';

        if (! is_dir($migrationPath) || empty(glob($migrationPath.'/*.php'))) {
            Log::info("ModuleMigrationService: No migrations found for '{$moduleName}'");

            return;
        }

        Log::info("ModuleMigrationService: Found migrations for '{$moduleName}'", [
            'path' => $migrationPath,
        ]);

        try {
            Artisan::call('module:migrate', [
                'module' => $moduleName,
                '--force' => true,
            ]);
            Log::info("ModuleMigrationService: Ran module:migrate for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::warning("ModuleMigrationService: module:migrate failed for '{$moduleName}'", [
                'error' => $e->getMessage(),
            ]);
        }

        Artisan::call('migrate', [
            '--path' => 'Modules/'.$moduleName.'/database/migrations',
            '--force' => true,
        ]);
        Log::info("ModuleMigrationService: Ran migrate for '{$moduleName}'");
    }

    /**
     * Rollback migrations for a module
     *
     * Rolls back all migrations for the specified module.
     *
     * @param  string  $moduleName  The name of the module
     */
    public function rollbackMigrations(string $moduleName): void
    {
        Log::info("ModuleMigrationService: Rolling back migrations for '{$moduleName}'");

        Artisan::call('module:migrate-rollback', [
            'module' => $moduleName,
            '--force' => true,
        ]);

        Log::info("ModuleMigrationService: Rolled back migrations for '{$moduleName}'");
    }
}
