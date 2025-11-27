<?php

namespace App\Listeners;

use App\Services\Modules\ModuleLifecycleService;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auto-install protected modules after database migrations complete
 *
 * Listens to MigrationsEnded event to automatically install essential protected modules
 * (like Core) on fresh database installations. Only runs if no modules are already installed,
 * preventing re-installation on subsequent migrations.
 */
class AutoInstallProtectedModules
{
    public function __construct(
        private ModuleLifecycleService $lifecycleService
    ) {}

    /**
     * Handle the MigrationsEnded event
     *
     * Automatically installs protected modules after migrations complete on a fresh database.
     * Skips if modules_statuses table doesn't exist or if modules are already installed.
     *
     * @param  MigrationsEnded  $event  The migrations ended event
     */
    public function handle(MigrationsEnded $event): void
    {
        if (! $this->tableExists('modules_statuses')) {
            return;
        }

        // Only auto-install on fresh databases (no installed modules yet)
        $hasInstalledModules = DB::table('modules_statuses')
            ->where('is_installed', true)
            ->exists();

        if ($hasInstalledModules) {
            return;
        }

        try {
            $this->lifecycleService->installProtectedModules();
        } catch (\Exception $e) {
            Log::error('Failed to auto-install protected modules', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Check if a database table exists
     *
     * Gracefully handles cases where database connection might not be available.
     *
     * @param  string  $tableName  The table name to check
     * @return bool True if table exists, false otherwise
     */
    private function tableExists(string $tableName): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }
}
