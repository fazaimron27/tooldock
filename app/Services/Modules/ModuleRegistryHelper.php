<?php

/**
 * Module Registry Helper
 *
 * Assists in post-module-operation tasks such as clearing caches,
 * rebuilding Artisan caches (routes, config), generating Ziggy
 * routes, and warming permission caches.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Modules;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Modules\Core\Services\PermissionCacheService;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Facades\Module as ModuleFacade;

/**
 * Class ModuleRegistryHelper
 *
 * Consolidates post-module-operation tasks into reusable methods.
 * Called by ModuleLifecycleService after install, uninstall, enable,
 * or disable operations to ensure all caches and registries are
 * synchronized with the current module state.
 *
 * @see \App\Services\Modules\ModuleLifecycleService For the primary consumer
 * @see \App\Services\Modules\DatabaseActivator For status synchronization
 * @see \Modules\Core\Services\PermissionCacheService For permission cache warming
 */
class ModuleRegistryHelper
{
    /**
     * Create a new ModuleRegistryHelper instance.
     *
     * @param  \Nwidart\Modules\Contracts\ActivatorInterface  $activator  Module activator for status management
     * @param  \App\Services\Modules\ModuleStatusService  $statusService  Service for module status cache
     */
    public function __construct(
        private ActivatorInterface $activator,
        private ModuleStatusService $statusService
    ) {}

    /**
     * Synchronize in-memory module status caches with the database.
     *
     * Reloads DatabaseActivator and ModuleStatusService caches to reflect
     * the current database state. No-op for FileActivator.
     */
    public function reloadStatuses(): void
    {
        if ($this->activator instanceof DatabaseActivator) {
            $this->activator->reloadStatuses();
        }

        $this->statusService->reloadCache();
    }

    /**
     * Re-discover and boot all modules.
     *
     * Clears the static module cache, scans filesystem for modules,
     * then registers and boots their service providers.
     */
    public function refresh(): void
    {
        ModuleFacade::resetModules();
        ModuleFacade::scan();
        ModuleFacade::register();
        ModuleFacade::boot();
    }

    /**
     * Rebuild route and config caches to include module changes.
     *
     * Routes are rebuilt synchronously to ensure immediate availability.
     * Config is rebuilt after response to avoid breaking Vite resolution.
     * Skipped if caches don't exist.
     */
    public function rebuildCachesIfNeeded(): void
    {
        $configCached = file_exists(app()->getCachedConfigPath());
        $routesCached = file_exists(app()->getCachedRoutesPath());

        if (! $configCached && ! $routesCached) {
            return;
        }

        $basePath = base_path();

        if ($routesCached) {
            Log::info('ModuleRegistryHelper: Rebuilding route cache (synchronous)');
            $this->runArtisanInSubprocess('route:clear', $basePath, throwOnFailure: true);
            $this->runArtisanInSubprocess('route:cache', $basePath, throwOnFailure: true);
        }

        if ($configCached) {
            if (app()->environment('local', 'development', 'testing')) {
                Log::info('ModuleRegistryHelper: Clearing config cache (dev mode, not re-caching)');
                $this->runArtisanInSubprocess('config:clear', $basePath);
            } else {
                Log::info('ModuleRegistryHelper: Deferring config cache rebuild to after response');
                app()->terminating(function () use ($basePath) {
                    Log::info('ModuleRegistryHelper: Rebuilding config cache (deferred)');
                    $this->runArtisanInSubprocess('config:clear', $basePath);
                    $this->runArtisanInSubprocess('config:cache', $basePath);
                });
            }
        }
    }

    /**
     * Execute an Artisan command in a fresh PHP process.
     *
     * @param  string  $command  The artisan command (e.g., 'route:cache')
     * @param  string  $basePath  Laravel application base path
     * @param  bool  $throwOnFailure  Throw exception on failure
     *
     * @throws \RuntimeException When command fails and $throwOnFailure is true
     */
    private function runArtisanInSubprocess(string $command, string $basePath, bool $throwOnFailure = false): void
    {
        $artisanPath = $basePath.'/artisan';
        $process = Process::path($basePath)->run("php {$artisanPath} {$command}");

        if (! $process->successful()) {
            $errorMessage = "ModuleRegistryHelper: {$command} failed";
            $context = [
                'exitCode' => $process->exitCode(),
                'output' => $process->output(),
                'errorOutput' => $process->errorOutput(),
            ];

            Log::error($errorMessage, $context);

            if ($throwOnFailure) {
                throw new \RuntimeException("{$errorMessage}: {$process->errorOutput()}");
            }
        } else {
            Log::debug("ModuleRegistryHelper: {$command} completed successfully");
        }
    }

    /**
     * Regenerate Ziggy route definitions for frontend route() helpers.
     *
     * Updates resources/js/ziggy.js and invalidates its OPcache entry.
     * Silently fails if Ziggy is not installed.
     */
    public function generateZiggyRoutes(): void
    {
        try {
            $commands = Artisan::all();
            if (isset($commands['ziggy:generate'])) {
                Artisan::call('ziggy:generate');

                $ziggyPath = resource_path('js/ziggy.js');
                if (function_exists('opcache_invalidate') && file_exists($ziggyPath)) {
                    opcache_invalidate($ziggyPath, true);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to generate Ziggy routes', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Warm the Spatie permission cache.
     *
     * Pre-loads permissions into Redis cache to ensure the first web request
     * after module operations doesn't suffer a cache miss penalty.
     * This is especially important after cache:clear or module installations.
     */
    public function warmPermissionCache(): void
    {
        try {
            app(PermissionCacheService::class)->warm();
            Log::debug('ModuleRegistryHelper: Permission cache warmed successfully');
        } catch (\Exception $e) {
            Log::warning('ModuleRegistryHelper: Failed to warm permission cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Complete a module operation by synchronizing all caches and registries.
     *
     * Reloads statuses, refreshes module registry, rebuilds Laravel caches,
     * regenerates Ziggy routes, and warms the permission cache.
     */
    public function finalize(): void
    {
        $this->reloadStatuses();
        $this->refresh();
        $this->rebuildCachesIfNeeded();
        $this->warmPermissionCache();
        $this->generateZiggyRoutes();
    }
}
