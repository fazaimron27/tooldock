<?php

/**
 * Protected Module Migration Service.
 *
 * Discovers and provides migration paths from modules marked as protected
 * in their module.json configuration. Ensures protected module migrations
 * are loaded during application bootstrap so they are available during
 * standard Laravel migration commands.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Services\Modules;

use Nwidart\Modules\Facades\Module;

/**
 * Service for loading migrations from protected modules.
 *
 * Handles the discovery and registration of migrations from modules
 * marked as protected, ensuring they are loaded during application bootstrap.
 */
class ProtectedModuleMigrationService
{
    /**
     * Get migration paths from all protected modules.
     *
     * Scans all modules and returns migration paths from those marked as protected.
     * This ensures protected module migrations are available during migrations.
     *
     * @return array<string> Array of migration paths
     */
    public function getMigrationPaths(): array
    {
        Module::scan();
        $allModules = Module::all();
        $migrationPaths = [];

        foreach ($allModules as $module) {
            if ($module->get('protected') === true) {
                $migrationPath = $module->getPath().'/database/migrations';
                if (is_dir($migrationPath)) {
                    $migrationPaths[] = $migrationPath;
                }
            }
        }

        return $migrationPaths;
    }
}
