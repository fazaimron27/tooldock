<?php

namespace Modules\Groups\App\Services;

use App\Services\Registry\MenuRegistry;
use Modules\Core\App\Services\PermissionCacheService;

/**
 * Centralized service for clearing caches related to groups.
 *
 * Reduces duplication by providing a single point for cache clearing
 * when groups, members, or permissions change.
 */
class GroupCacheService
{
    public function __construct(
        private PermissionCacheService $permissionCacheService,
        private MenuRegistry $menuRegistry,
        private GroupPermissionCacheService $groupPermissionCacheService
    ) {}

    /**
     * Clear all caches when group membership changes.
     *
     * @param  array<string>  $userIds  Array of user IDs affected
     * @return void
     */
    public function clearForMembershipChange(array $userIds): void
    {
        $this->permissionCacheService->clear();

        foreach ($userIds as $userId) {
            $this->groupPermissionCacheService->clearForUser($userId);
            $this->menuRegistry->clearCacheForUser($userId);
        }
    }

    /**
     * Clear all caches when group permissions change.
     *
     * @param  array<string>  $userIds  Array of user IDs in the group
     * @return void
     */
    public function clearForPermissionChange(array $userIds): void
    {
        $this->permissionCacheService->clear();

        foreach ($userIds as $userId) {
            $this->groupPermissionCacheService->clearForUser($userId);
            $this->menuRegistry->clearCacheForUser($userId);
        }
    }

    /**
     * Clear all caches when group roles change.
     *
     * @param  array<string>  $userIds  Array of user IDs in the group
     * @return void
     */
    public function clearForRoleChange(array $userIds): void
    {
        $this->permissionCacheService->clear();

        foreach ($userIds as $userId) {
            $this->groupPermissionCacheService->clearForUser($userId);
            $this->menuRegistry->clearCacheForUser($userId);
        }
    }

    /**
     * Clear all caches when a group is deleted.
     *
     * @param  array<string>  $userIds  Array of user IDs who were in the group
     * @return void
     */
    public function clearForGroupDeletion(array $userIds): void
    {
        $this->clearForMembershipChange($userIds);
    }
}
