<?php

/**
 * Permission Cache Service.
 *
 * Manages clearing and warming of the Spatie permission
 * cache to keep authorization data synchronized.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

/**
 * Centralized service for managing permission cache.
 *
 * Provides a single point of control for clearing permission caches,
 * making it easier to extend with additional cache strategies in the future.
 */
class PermissionCacheService
{
    /**
     * Clear all permission caches.
     *
     * Clears the global permission cache managed by Spatie Permission package,
     * then immediately warms the cache to avoid slow first requests.
     * This should be called whenever permissions or roles are modified.
     *
     * @return void
     */
    public function clear(): void
    {
        $registrar = app(PermissionRegistrar::class);

        $registrar->forgetCachedPermissions();

        $this->warm();
    }

    /**
     * Warm the permission cache.
     *
     * Pre-loads permissions into the cache to ensure fast permission checks.
     * This is called automatically after clear(), but can also be called
     * manually if needed.
     *
     * @return void
     */
    public function warm(): void
    {
        try {
            $registrar = app(PermissionRegistrar::class);

            $registrar->clearPermissionsCollection();

            $registrar->initializeCache();

            $permissions = $registrar->getPermissions();

            Log::debug('PermissionCacheService: Cache warmed successfully', [
                'permissions_count' => $permissions->count(),
                'cache_key' => $registrar->cacheKey,
            ]);
        } catch (\Exception $e) {
            Log::warning('PermissionCacheService: Failed to warm cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear permission cache for a specific user.
     *
     * Note: Spatie Permission uses a global cache, so clearing for one user
     * clears for all users. This method exists for future extensibility
     * if user-specific caching is implemented.
     *
     * @param  string  $userId  User ID (currently unused, reserved for future use)
     * @return void
     */
    public function clearForUser(string $userId): void
    {
        $this->clear();
    }
}
