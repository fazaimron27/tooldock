<?php

namespace Modules\Core\Services;

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
     * Clears the global permission cache managed by Spatie Permission package.
     * This should be called whenever permissions or roles are modified.
     */
    public function clear(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Clear permission cache for a specific user.
     *
     * Note: Spatie Permission uses a global cache, so clearing for one user
     * clears for all users. This method exists for future extensibility
     * if user-specific caching is implemented.
     *
     * @param  string  $userId  User ID (currently unused, reserved for future use)
     */
    public function clearForUser(string $userId): void
    {
        $this->clear();
    }
}
