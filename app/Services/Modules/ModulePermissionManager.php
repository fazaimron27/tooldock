<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\App\Services\PermissionCacheService;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Spatie\Permission\Models\Permission;

class ModulePermissionManager
{
    public function __construct(
        private RepositoryInterface $moduleRepository
    ) {}

    /**
     * Run permission seeder for a module if it exists.
     *
     * Permission seeders are required for module functionality and should
     * always run during installation, even without the --seed flag.
     *
     * @param  string  $moduleName  The name of the module
     */
    public function runSeeder(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);
        $seederPath = $module->getPath().'/database/seeders';
        $permissionSeederClass = "Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}PermissionSeeder";

        $permissionSeederFile = $seederPath."/{$moduleName}PermissionSeeder.php";
        if (! file_exists($permissionSeederFile)) {
            Log::info("ModulePermissionManager: No permission seeder found for '{$moduleName}'");

            return;
        }

        if (! class_exists($permissionSeederClass)) {
            Log::warning("ModulePermissionManager: Permission seeder class not found: {$permissionSeederClass}");

            return;
        }

        try {
            Log::info("ModulePermissionManager: Running permission seeder for '{$moduleName}'");
            $seeder = app($permissionSeederClass);
            $seeder->run();
            Log::info("ModulePermissionManager: Permission seeder completed for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::error("ModulePermissionManager: Failed to run permission seeder for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Clean up module permissions when uninstalling a module.
     *
     * Removes all permissions that start with the module prefix (e.g., "blog.*", "newsletter.*")
     * and cleans up related pivot table entries.
     *
     * @param  string  $moduleName  The name of the module being uninstalled
     */
    public function cleanup(string $moduleName): void
    {
        $modulePrefix = strtolower($moduleName).'.';

        try {
            Log::info("ModulePermissionManager: Cleaning up permissions for '{$moduleName}'", [
                'prefix' => $modulePrefix,
            ]);

            $permissions = Permission::where('name', 'like', $modulePrefix.'%')->get();

            if ($permissions->isEmpty()) {
                Log::info("ModulePermissionManager: No permissions found for '{$moduleName}'");
                app(PermissionCacheService::class)->clear();

                return;
            }

            $permissionIds = $permissions->pluck('id')->toArray();
            $permissionNames = $permissions->pluck('name')->toArray();

            Log::info('ModulePermissionManager: Found permissions to remove', [
                'count' => count($permissionIds),
                'permissions' => $permissionNames,
            ]);

            $rolePermissionsDeleted = DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            Log::info('ModulePermissionManager: Removed permissions from roles', [
                'count' => $rolePermissionsDeleted,
            ]);

            $modelPermissionsDeleted = DB::table('model_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            Log::info('ModulePermissionManager: Removed permissions from models', [
                'count' => $modelPermissionsDeleted,
            ]);

            $permissionsDeleted = Permission::whereIn('id', $permissionIds)->delete();

            Log::info('ModulePermissionManager: Deleted permissions', [
                'count' => $permissionsDeleted,
            ]);

            app(PermissionCacheService::class)->clear();

            Log::info("ModulePermissionManager: Permission cleanup completed for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::error("ModulePermissionManager: Failed to cleanup permissions for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
