<?php

namespace Modules\Core\App\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\GroupRegistry;
use App\Services\Registry\MenuRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionCacheService;

/**
 * Observer for User model events.
 *
 * Automatically assigns default Guest group to newly created users
 * if they don't already have any groups assigned.
 */
class UserObserver
{
    public function __construct(
        private PermissionCacheService $permissionCacheService,
        private MenuRegistry $menuRegistry,
        private DashboardWidgetRegistry $dashboardWidgetRegistry,
        private GroupRegistry $groupRegistry
    ) {}

    /**
     * Handle the User "created" event.
     *
     * Assigns the default Guest group (configured in core config) to new users
     * if they don't already have any groups assigned.
     */
    public function created(User $user): void
    {
        if (! $user->groups()->exists()) {
            try {
                $defaultGroupName = config('core.default_group', 'Guest');
                $defaultGroup = $this->groupRegistry->getGroup($defaultGroupName);

                if (! $defaultGroup) {
                    $defaultGroup = \Modules\Groups\Models\Group::where('name', $defaultGroupName)->first();
                }

                if ($defaultGroup) {
                    $user->groups()->attach($defaultGroup->id);
                    $this->permissionCacheService->clear();
                    $this->menuRegistry->clearCacheForUser($user->id);
                    $user->load('groups');
                }

                $this->dashboardWidgetRegistry->clearCache(null, 'Core');
            } catch (\Exception $e) {
                Log::warning('Failed to assign default group to user: '.$e->getMessage(), [
                    'user_id' => $user->id,
                    'group' => $defaultGroupName ?? 'unknown',
                ]);
            }
        }

        $this->clearUserSearchCache();
    }

    /**
     * Handle the User "updated" event.
     *
     * Clears user search cache when user name or email changes.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged(['name', 'email'])) {
            $this->clearUserSearchCache();
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * Clears user search cache when a user is deleted.
     */
    public function deleted(User $user): void
    {
        $this->clearUserSearchCache();
    }

    /**
     * Clear user search cache.
     *
     * Uses cache tags for efficient bulk invalidation of all user search results.
     * This ensures that when users are created, updated, or deleted, all cached
     * search results are invalidated.
     *
     * Note: Cache tags are only supported by Redis and Memcached drivers.
     * For other drivers (file, database, dynamodb), the exception is caught and
     * we rely on TTL expiration for cache invalidation.
     */
    private function clearUserSearchCache(): void
    {
        try {
            /**
             * Invalidate all user search caches using tag-based flush.
             * Only supported by Redis and Memcached drivers for efficient bulk operations.
             */
            Cache::tags(['users', 'user_search'])->flush();
        } catch (\Exception $e) {
            /**
             * Cache tags not available for file/database/dynamodb drivers.
             * Fallback to TTL-based expiration for cache invalidation.
             */
            Log::debug('UserObserver: Cache tags not available, relying on TTL for cache expiration', [
                'cache_driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
