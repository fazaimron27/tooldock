<?php

namespace Modules\Groups\Services;

use App\Services\Cache\CacheService;

/**
 * Service for caching group-based permissions per user.
 *
 * Provides user-specific caching of group permissions to improve
 * performance when checking permissions multiple times in a request.
 */
class GroupPermissionCacheService
{
    private const CACHE_TAG = 'group_permissions';

    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Get cached group permissions for a user.
     *
     * @param  string  $userId  The user ID
     * @return array<string>|null Array of permission names or null if not cached
     */
    public function get(string $userId): ?array
    {
        $cacheKey = $this->getCacheKey($userId);

        return $this->cacheService->get($cacheKey, null, self::CACHE_TAG, 'GroupPermissionCache');
    }

    /**
     * Cache group permissions for a user.
     *
     * @param  string  $userId  The user ID
     * @param  array<string>  $permissions  Array of permission names
     * @return bool Success status
     */
    public function put(string $userId, array $permissions): bool
    {
        $cacheKey = $this->getCacheKey($userId);

        return $this->cacheService->put(
            $cacheKey,
            $permissions,
            self::CACHE_TTL,
            self::CACHE_TAG,
            'GroupPermissionCache'
        );
    }

    /**
     * Clear cached group permissions for a specific user.
     *
     * @param  string  $userId  The user ID
     * @return void
     */
    public function clearForUser(string $userId): void
    {
        $cacheKey = $this->getCacheKey($userId);
        $this->cacheService->forget($cacheKey, self::CACHE_TAG, 'GroupPermissionCache');
    }

    /**
     * Clear all cached group permissions.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->cacheService->clearTag(self::CACHE_TAG, 'GroupPermissionCache');
    }

    /**
     * Get the cache key for a user.
     *
     * @param  string  $userId  The user ID
     * @return string
     */
    private function getCacheKey(string $userId): string
    {
        return "group_permissions:user:{$userId}";
    }
}
