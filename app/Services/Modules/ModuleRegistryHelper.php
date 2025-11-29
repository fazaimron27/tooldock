<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Facades\Module as ModuleFacade;

class ModuleRegistryHelper
{
    public function __construct(
        private ActivatorInterface $activator,
        private ModuleStatusService $statusService
    ) {}

    /**
     * Reload statuses if the activator is a DatabaseActivator
     *
     * DatabaseActivator and ModuleStatusService both cache module statuses in memory.
     * After external database changes (like direct SQL updates), we need to reload
     * both caches to keep them in sync.
     *
     * This is a no-op for other activator types (e.g., FileActivator).
     */
    public function reloadStatuses(): void
    {
        if ($this->activator instanceof DatabaseActivator) {
            $this->activator->reloadStatuses();
        }

        $this->statusService->reloadCache();
    }

    /**
     * Refresh the module registry by scanning, registering, and booting modules
     *
     * Ensures the nwidart/laravel-modules package discovers any new or changed modules
     * after installation/enabling. This is necessary because:
     * - scan() discovers modules in the filesystem
     * - register() registers service providers
     * - boot() boots registered providers
     */
    public function refresh(): void
    {
        ModuleFacade::scan();
        ModuleFacade::register();
        ModuleFacade::boot();
    }

    /**
     * Clear application caches (config and routes)
     *
     * Clears Laravel's cached configuration and routes to ensure changes from
     * newly installed/enabled modules are picked up immediately.
     */
    public function clearCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
    }

    /**
     * Generate Ziggy routes using the default Artisan command
     *
     * Regenerates the Ziggy route definitions file (resources/js/ziggy.js) to include
     * routes from newly installed/enabled modules. This allows the frontend to use
     * route() helper functions for module routes.
     */
    public function generateZiggyRoutes(): void
    {
        Artisan::call('ziggy:generate');
    }

    /**
     * Finalize a module operation by performing all cleanup steps
     *
     * Centralized cleanup method called after enable/disable operations.
     * Ensures consistency by:
     * 1. Reloading activator statuses (if DatabaseActivator) and ModuleStatusService cache
     * 2. Refreshing module registry (discover new modules)
     * 3. Clearing caches (config, routes)
     * 4. Regenerating Ziggy routes (for frontend route helpers)
     */
    public function finalize(): void
    {
        $this->reloadStatuses();
        $this->refresh();
        $this->clearCaches();
        $this->generateZiggyRoutes();
    }
}
