<?php

namespace App\Services\Modules;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

class DatabaseActivator implements ActivatorInterface
{
    /**
     * Array of modules activation statuses (cached during request lifecycle)
     *
     * @var array<string, bool>
     */
    private array $modulesStatuses = [];

    public function __construct(Container $app)
    {
        $this->loadStatuses();
    }

    /**
     * Load all module statuses from database into cache
     */
    private function loadStatuses(): void
    {
        if (! $this->tableExists()) {
            $this->modulesStatuses = [];

            return;
        }

        $statuses = DB::table('modules_statuses')
            ->pluck('is_active', 'name')
            ->map(fn ($value) => (bool) $value)
            ->toArray();

        $this->modulesStatuses = $statuses;
    }

    /**
     * Check if modules_statuses table exists
     */
    private function tableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('modules_statuses');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Persist status changes to database
     */
    private function persistStatus(string $name, bool $status): void
    {
        if (! $this->tableExists()) {
            return;
        }

        DB::table('modules_statuses')->updateOrInsert(
            ['name' => $name],
            ['is_active' => $status, 'updated_at' => now()]
        );

        // Update cache
        $this->modulesStatuses[$name] = $status;
    }

    /**
     * Reload statuses from database (useful after external changes)
     */
    public function reloadStatuses(): void
    {
        $this->loadStatuses();
    }

    /**
     * {@inheritDoc}
     */
    public function enable(Module $module): void
    {
        $this->setActiveByName($module->getName(), true);
    }

    /**
     * {@inheritDoc}
     */
    public function disable(Module $module): void
    {
        $this->setActiveByName($module->getName(), false);
    }

    /**
     * {@inheritDoc}
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
     */
    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    /**
     * {@inheritDoc}
     */
    public function setActiveByName(string $name, bool $status): void
    {
        $this->persistStatus($name, $status);
    }

    /**
     * {@inheritDoc}
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
