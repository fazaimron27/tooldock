<?php

namespace App\Services\Modules;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

/**
 * Database-based module activator implementation
 *
 * Stores module activation status in the database (modules_statuses table) instead of files.
 * This provides better multi-server support, easier querying, and integration with
 * custom module lifecycle management (is_installed, version tracking, etc.).
 *
 * The activator is used by nwidart/laravel-modules to determine which modules should
 * be loaded during application boot. ModuleLifecycleService uses this to enable/disable
 * modules, but the package itself uses methods like hasStatus() to check activation.
 */
class DatabaseActivator implements ActivatorInterface
{
    /**
     * Array of modules activation statuses (cached during request lifecycle)
     *
     * Loaded once per request to avoid repeated database queries.
     * Maps module name => is_active (bool)
     *
     * @var array<string, bool>
     */
    private array $modulesStatuses = [];

    public function __construct(Container $app)
    {
        // Load all module statuses into memory cache on construction
        $this->loadStatuses();
    }

    /**
     * Load all module statuses from database into cache
     *
     * Called on construction and when reloadStatuses() is invoked.
     * Populates the in-memory cache to avoid repeated database queries.
     */
    private function loadStatuses(): void
    {
        if (! $this->tableExists()) {
            $this->modulesStatuses = [];

            return;
        }

        // Load all module activation statuses into memory
        $statuses = DB::table('modules_statuses')
            ->pluck('is_active', 'name')
            ->map(fn ($value) => (bool) $value)
            ->toArray();

        $this->modulesStatuses = $statuses;
    }

    /**
     * Check if modules_statuses table exists
     *
     * Gracefully handles cases where migrations haven't run yet.
     *
     * @return bool True if table exists, false otherwise
     */
    private function tableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('modules_statuses');
        } catch (\Exception $e) {
            // Database connection might not be available during early bootstrap
            return false;
        }
    }

    /**
     * Persist status changes to database
     *
     * Updates both the database and in-memory cache atomically.
     *
     * @param  string  $name  Module name
     * @param  bool  $status  Activation status (true = enabled, false = disabled)
     */
    private function persistStatus(string $name, bool $status): void
    {
        if (! $this->tableExists()) {
            return;
        }

        // Update or insert the module's activation status
        DB::table('modules_statuses')->updateOrInsert(
            ['name' => $name],
            ['is_active' => $status, 'updated_at' => now()]
        );

        // Update in-memory cache to keep it in sync
        $this->modulesStatuses[$name] = $status;
    }

    /**
     * Reload statuses from database (useful after external changes)
     *
     * Called by ModuleLifecycleService after database changes to ensure
     * the in-memory cache stays synchronized with the database.
     *
     * Public method used by ModuleLifecycleService::reloadStatusesIfNeeded()
     */
    public function reloadStatuses(): void
    {
        $this->loadStatuses();
    }

    /**
     * {@inheritDoc}
     *
     * Enable a module (set is_active = true).
     * Used by ModuleLifecycleService and nwidart/laravel-modules package.
     */
    public function enable(Module $module): void
    {
        $this->setActiveByName($module->getName(), true);
    }

    /**
     * {@inheritDoc}
     *
     * Disable a module (set is_active = false).
     * Used by ModuleLifecycleService and nwidart/laravel-modules package.
     */
    public function disable(Module $module): void
    {
        $this->setActiveByName($module->getName(), false);
    }

    /**
     * {@inheritDoc}
     *
     * Check if a module has a specific activation status.
     * Used by nwidart/laravel-modules package internally (e.g., ModuleFacade::isEnabled()).
     * Not directly used by ModuleLifecycleService, but required by ActivatorInterface.
     */
    public function hasStatus(Module|string $module, bool $status): bool
    {
        $name = $module instanceof Module ? $module->getName() : $module;

        if (! isset($this->modulesStatuses[$name])) {
            return $status === false;
        }

        return $this->modulesStatuses[$name] === $status;
    }

    /**
     * {@inheritDoc}
     *
     * Set module activation status using Module object.
     * Wrapper around setActiveByName() for convenience.
     * Not directly used by ModuleLifecycleService, but required by ActivatorInterface.
     */
    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    /**
     * {@inheritDoc}
     *
     * Set module activation status by module name.
     * Internal method used by enable() and disable().
     * Persists to database and updates in-memory cache.
     */
    public function setActiveByName(string $name, bool $status): void
    {
        $this->persistStatus($name, $status);
    }

    /**
     * {@inheritDoc}
     *
     * Delete a module's activation record from database.
     * Used when a module is completely removed from the system.
     * Not directly used by ModuleLifecycleService (which uses uninstall instead),
     * but required by ActivatorInterface for module deletion scenarios.
     */
    public function delete(Module $module): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $name = $module->getName();

        DB::table('modules_statuses')->where('name', $name)->delete();

        unset($this->modulesStatuses[$name]);
    }

    /**
     * {@inheritDoc}
     *
     * Reset all module activation statuses (truncate table).
     * Useful for testing or complete system reset.
     * Not directly used by ModuleLifecycleService, but required by ActivatorInterface.
     */
    public function reset(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        DB::table('modules_statuses')->truncate();
        $this->modulesStatuses = [];
    }
}
