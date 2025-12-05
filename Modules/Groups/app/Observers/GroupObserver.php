<?php

namespace Modules\Groups\App\Observers;

use App\Services\Registry\MenuRegistry;
use Modules\Groups\App\Services\GroupCacheService;
use Modules\Groups\Models\Group;

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
    }

    /**
     * Handle the Group "deleted" event.
     *
     * Clear permission cache when a group is deleted.
     * Also clear menu cache for all users who were in this group.
     */
    public function deleted(Group $group): void
    {
        $userIds = $group->users()->pluck('users.id')->toArray();
        if (! empty($userIds)) {
            $this->cacheService->clearForGroupDeletion($userIds);
        }
    }
}
