<?php

namespace Modules\Groups\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\MenuRegistry;
use Modules\Groups\Models\Group;
use Modules\Groups\Services\GroupCacheService;

class GroupObserver
{
    public function __construct(
        private GroupCacheService $cacheService,
        private MenuRegistry $menuRegistry
    ) {}

    /**
     * Handle the Group "saved" event.
     *
     * Note: This only fires when Group model attributes change (name, slug, description).
     * Relationship changes (users, permissions) are handled in controllers.
     *
     * When group attributes change, we only need to clear menu cache if the group name
     * changed (affects menu display), not permission cache (permissions didn't change).
     */
    public function saved(Group $group): void
    {
        if (! $group->wasChanged() && ! $group->wasRecentlyCreated) {
            return;
        }

        if ($group->wasChanged('name') || $group->wasRecentlyCreated) {
            $userIds = $group->users()->pluck('users.id')->toArray();
            if (! empty($userIds)) {
                foreach ($userIds as $userId) {
                    $this->menuRegistry->clearCacheForUser($userId);
                }
            }
        }

        $this->clearWidgetCache();
    }

    /**
     * Handle the Group "deleting" event.
     *
     * Clear permission cache when a group is deleted.
     * Also clear menu cache for all users who were in this group.
     *
     * Note: We use "deleting" instead of "deleted" because we need to
     * access the relationship before the pivot table entries are removed.
     * The "deleting" event fires before deletion, so relationships are still accessible.
     */
    public function deleting(Group $group): void
    {
        $userIds = $group->users()->pluck('users.id')->toArray();

        if (! empty($userIds)) {
            $this->cacheService->clearForGroupDeletion($userIds);
        }

        $this->clearWidgetCache();
    }

    /**
     * Clear Groups module widget cache.
     */
    private function clearWidgetCache(): void
    {
        app(DashboardWidgetRegistry::class)->clearCache(null, 'Groups');
    }
}
