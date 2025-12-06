<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleStatusService
{
    /**
     * In-memory cache for module statuses (request lifecycle)
     *
     * Structure: ['moduleName' => ['is_installed' => bool, 'is_active' => bool, 'version' => string]]
     * Loaded lazily on first read operation to avoid unnecessary queries.
     *
     * @var array<string, array{is_installed: bool, is_active: bool, version: string}>|null
     */
    private ?array $statusCache = null;

    /**
     * Load all module statuses from database into memory cache
     *
     * Called lazily on first read operation to avoid unnecessary queries.
     * Uses a single query to load all module statuses for efficient caching.
     *
     * @return array<string, array{is_installed: bool, is_active: bool, version: string}>
     */
    private function loadStatusCache(): array
    {
        if ($this->statusCache !== null) {
            return $this->statusCache;
        }

        $statuses = DB::table('modules_statuses')
            ->select('name', 'is_installed', 'is_active', 'version')
            ->get();

        $this->statusCache = [];

        foreach ($statuses as $status) {
            $this->statusCache[$status->name] = [
                'is_installed' => (bool) $status->is_installed,
                'is_active' => (bool) $status->is_active,
                'version' => $status->version ?? '1.0.0',
            ];
        }

        return $this->statusCache;
    }

    /**
     * Clear the in-memory status cache
     *
     * Called after write operations to ensure cache stays synchronized with database.
     */
    private function clearCache(): void
    {
        $this->statusCache = null;
    }

    /**
     * Reload the status cache from database
     *
     * Public method to force cache reload, useful for testing or after external database changes.
     */
    public function reloadCache(): void
    {
        $this->clearCache();
        $this->loadStatusCache();
    }

    /**
     * Mark a module as installed in the database
     *
     * Sets is_installed = true, records installed_at timestamp, and updates version.
     *
     * @param  string  $moduleName  The name of the module
     * @param  string  $version  The version of the module
     */
    public function markAsInstalled(string $moduleName, string $version): void
    {
        $exists = DB::table('modules_statuses')->where('name', $moduleName)->exists();

        if ($exists) {
            DB::table('modules_statuses')
                ->where('name', $moduleName)
                ->update([
                    'is_installed' => true,
                    'installed_at' => now(),
                    'version' => $version,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('modules_statuses')->insert([
                'id' => (string) Str::orderedUuid(),
                'name' => $moduleName,
                'is_installed' => true,
                'is_active' => false,
                'installed_at' => now(),
                'version' => $version,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->clearCache();
    }

    /**
     * Mark a module as uninstalled in the database
     *
     * Sets is_installed = false.
     *
     * @param  string  $moduleName  The name of the module
     */
    public function markAsUninstalled(string $moduleName): void
    {
        DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->update([
                'is_installed' => false,
                'updated_at' => now(),
            ]);

        $this->clearCache();
    }

    /**
     * Set the active status of a module
     *
     * Updates the is_active flag in the database.
     *
     * @param  string  $moduleName  The name of the module
     * @param  bool  $active  True to enable, false to disable
     */
    public function setActive(string $moduleName, bool $active): void
    {
        $exists = DB::table('modules_statuses')->where('name', $moduleName)->exists();

        if ($exists) {
            DB::table('modules_statuses')
                ->where('name', $moduleName)
                ->update([
                    'is_active' => $active,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('modules_statuses')->insert([
                'id' => (string) Str::orderedUuid(),
                'name' => $moduleName,
                'is_active' => $active,
                'is_installed' => false,
                'version' => '1.0.0',
                'installed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->clearCache();
    }

    /**
     * Check if a module is installed
     *
     * Uses in-memory cache to avoid repeated database queries.
     *
     * @param  string  $moduleName  The name of the module
     * @return bool True if the module is installed, false otherwise
     */
    public function isInstalled(string $moduleName): bool
    {
        $cache = $this->loadStatusCache();

        return isset($cache[$moduleName]) && $cache[$moduleName]['is_installed'] === true;
    }

    /**
     * Check if a module is active
     *
     * Uses in-memory cache to avoid repeated database queries.
     *
     * @param  string  $moduleName  The name of the module
     * @return bool True if the module is active, false otherwise
     */
    public function isActive(string $moduleName): bool
    {
        $cache = $this->loadStatusCache();

        return isset($cache[$moduleName]) && $cache[$moduleName]['is_active'] === true;
    }

    /**
     * Register a new module in the database
     *
     * Inserts a new module record with default values (is_installed=false, is_active=false).
     *
     * @param  string  $moduleName  The name of the module
     * @param  string  $version  The version of the module
     */
    public function register(string $moduleName, string $version): void
    {
        DB::table('modules_statuses')->insert([
            'id' => (string) Str::orderedUuid(),
            'name' => $moduleName,
            'is_active' => false,
            'is_installed' => false,
            'version' => $version,
            'installed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->clearCache();
    }

    /**
     * Update the version of a module
     *
     * Note: Version updates don't affect status checks, but we clear cache
     * to ensure consistency if version is used elsewhere.
     *
     * @param  string  $moduleName  The name of the module
     * @param  string  $version  The new version
     */
    public function updateVersion(string $moduleName, string $version): void
    {
        DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->update([
                'version' => $version,
                'updated_at' => now(),
            ]);

        $this->clearCache();
    }

    /**
     * Check if a module record exists in the database
     *
     * Uses in-memory cache to avoid repeated database queries.
     *
     * @param  string  $moduleName  The name of the module
     * @return bool True if the record exists, false otherwise
     */
    public function exists(string $moduleName): bool
    {
        $cache = $this->loadStatusCache();

        return isset($cache[$moduleName]);
    }

    /**
     * Get all installed module names
     *
     * Uses in-memory cache to avoid repeated database queries.
     *
     * @return array<string> Array of installed module names
     */
    public function getInstalledModuleNames(): array
    {
        $cache = $this->loadStatusCache();
        $installed = [];

        foreach ($cache as $moduleName => $status) {
            if ($status['is_installed'] === true) {
                $installed[] = $moduleName;
            }
        }

        return $installed;
    }

    /**
     * Get all module statuses with version
     *
     * Returns all module statuses including version information.
     * Uses in-memory cache to avoid repeated database queries.
     *
     * @return array<string, array{is_installed: bool, is_active: bool, version: string}> Map of module name to status data
     */
    public function getAllStatusesWithVersion(): array
    {
        return $this->loadStatusCache();
    }
}
