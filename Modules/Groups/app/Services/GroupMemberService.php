<?php

namespace Modules\Groups\App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AuditLog\App\Enums\AuditLogEvent;
use Modules\AuditLog\App\Jobs\CreateAuditLogJob;
use Modules\Core\App\Models\User;
use Modules\Groups\Models\Group;

/**
 * Service for managing group member operations.
 *
 * Handles business logic for adding, removing, and transferring members
 * between groups, including audit logging and cache management.
 */
class GroupMemberService
{
    public function __construct(
        private GroupCacheService $cacheService
    ) {}

    /**
     * Add members to the specified group.
     *
     * Bulk adds multiple users to a group. Skips users that are already members.
     * All operations are performed within a database transaction to ensure data integrity.
     * Changes are logged to the audit log and permission caches are cleared.
     *
     * @param  Group  $group  The group to add members to
     * @param  array<int|string>  $userIds  Array of user IDs to add
     * @return array{count: int, skipped: int, message: string}
     */
    public function addMembers(Group $group, array $userIds): array
    {
        return DB::transaction(function () use ($group, $userIds) {
            $largeGroupThreshold = (int) settings('groups_large_group_threshold', 100);

            $existingInGroup = DB::table('groups_users')
                ->where('group_id', $group->id)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $newUserIds = array_diff($userIds, $existingInGroup);

            if (empty($newUserIds)) {
                return [
                    'count' => 0,
                    'skipped' => count($userIds),
                    'message' => 'All selected users are already members of this group.',
                ];
            }

            $newUsersData = User::whereIn('id', $newUserIds)
                ->select('id', 'name')
                ->get();
            $newUserNames = $newUsersData->pluck('name')->sort()->values()->toArray();

            $auditData = $this->prepareMemberAuditLogData($group, $largeGroupThreshold);
            $oldMemberIds = $auditData['oldIds'];
            $oldMemberNames = $auditData['oldNames'];
            $oldCount = $auditData['oldCount'];

            $insertData = array_map(fn ($userId) => [
                'group_id' => $group->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $newUserIds);

            DB::table('groups_users')->insert($insertData);

            if ($oldCount <= $largeGroupThreshold) {
                $newMemberData = $this->calculateNewMemberData(
                    $oldMemberIds,
                    $oldMemberNames,
                    [],
                    $newUserIds,
                    $newUserNames
                );
                $newMemberIds = $newMemberData['ids'];
                $newMemberNames = $newMemberData['names'];
            } else {
                $newCount = $oldCount + count($newUserIds);
                $newMemberIds = ["[{$newCount} members]"];
                $newMemberNames = ["[{$newCount} members]"];
            }

            $this->dispatchMemberChangeAuditLog(
                $group,
                $oldMemberNames,
                $oldMemberIds,
                $newMemberNames,
                $newMemberIds
            );

            $this->cacheService->clearForMembershipChange($newUserIds);

            $count = count($newUserIds);
            $skipped = count($userIds) - $count;

            $message = "{$count} member".($count === 1 ? '' : 's').' added successfully.';
            if ($skipped > 0) {
                $message .= " {$skipped} ".($skipped === 1 ? 'was' : 'were').' already a member of this group.';
            }

            return [
                'count' => $count,
                'skipped' => $skipped,
                'message' => $message,
            ];
        });
    }

    /**
     * Remove members from the specified group.
     *
     * Bulk removes multiple users from a group. Only removes users that are actually members.
     * All operations are performed within a database transaction to ensure data integrity.
     * Changes are logged to the audit log and permission caches are cleared.
     *
     * @param  Group  $group  The group to remove members from
     * @param  array<int|string>  $userIds  Array of user IDs to remove
     * @return array{count: int, message: string}
     */
    public function removeMembers(Group $group, array $userIds): array
    {
        return DB::transaction(function () use ($group, $userIds) {
            $largeGroupThreshold = (int) settings('groups_large_group_threshold', 100);

            $existingInGroup = DB::table('groups_users')
                ->where('group_id', $group->id)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $usersToRemove = array_intersect($userIds, $existingInGroup);

            if (empty($usersToRemove)) {
                return [
                    'count' => 0,
                    'message' => 'None of the selected users are members of this group.',
                ];
            }

            $auditData = $this->prepareMemberAuditLogData($group, $largeGroupThreshold);
            $oldMemberIds = $auditData['oldIds'];
            $oldMemberNames = $auditData['oldNames'];
            $oldCount = $auditData['oldCount'];

            DB::table('groups_users')
                ->where('group_id', $group->id)
                ->whereIn('user_id', $usersToRemove)
                ->delete();

            if ($oldCount <= $largeGroupThreshold) {
                $newMemberData = $this->calculateNewMemberData(
                    $oldMemberIds,
                    $oldMemberNames,
                    $usersToRemove,
                    [],
                    []
                );
                $newMemberIds = $newMemberData['ids'];
                $newMemberNames = $newMemberData['names'];
            } else {
                $newCount = $oldCount - count($usersToRemove);
                $newMemberIds = ["[{$newCount} members]"];
                $newMemberNames = ["[{$newCount} members]"];
            }

            $this->dispatchMemberChangeAuditLog(
                $group,
                $oldMemberNames,
                $oldMemberIds,
                $newMemberNames,
                $newMemberIds
            );

            $this->cacheService->clearForMembershipChange($usersToRemove);

            $count = count($usersToRemove);

            return [
                'count' => $count,
                'message' => "{$count} member".($count === 1 ? '' : 's').' removed successfully.',
            ];
        });
    }

    /**
     * Transfer multiple members from the current group to another group.
     *
     * Removes users from the source group and adds them to the target group.
     * Users already in the target group are skipped. All operations are performed
     * within a database transaction to ensure data integrity. Changes are logged
     * to the audit log for both groups and permission caches are cleared.
     *
     * @param  Group  $sourceGroup  The source group to transfer members from
     * @param  Group  $targetGroup  The target group to transfer members to
     * @param  array<int|string>  $userIds  Array of user IDs to transfer
     * @return array{count: int, alreadyInTarget: int, message: string}
     */
    public function transferMembers(Group $sourceGroup, Group $targetGroup, array $userIds): array
    {
        return DB::transaction(function () use ($sourceGroup, $targetGroup, $userIds) {
            $largeGroupThreshold = (int) settings('groups_large_group_threshold', 100);

            $usersData = DB::table('users')
                ->select('id', 'name')
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');

            $existingInTarget = DB::table('groups_users')
                ->where('group_id', $targetGroup->id)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $usersToAdd = array_diff($userIds, $existingInTarget);

            $sourceAuditData = $this->prepareMemberAuditLogData($sourceGroup, $largeGroupThreshold);
            $oldSourceMemberIds = $sourceAuditData['oldIds'];
            $oldSourceMemberNames = $sourceAuditData['oldNames'];
            $oldSourceCount = $sourceAuditData['oldCount'];

            $targetAuditData = $this->prepareMemberAuditLogData($targetGroup, $largeGroupThreshold);
            $oldTargetMemberIds = $targetAuditData['oldIds'];
            $oldTargetMemberNames = $targetAuditData['oldNames'];
            $oldTargetCount = $targetAuditData['oldCount'];

            DB::table('groups_users')
                ->where('group_id', $sourceGroup->id)
                ->whereIn('user_id', $userIds)
                ->delete();

            if (! empty($usersToAdd)) {
                $insertData = array_map(fn ($userId) => [
                    'group_id' => $targetGroup->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $usersToAdd);

                DB::table('groups_users')->insert($insertData);
            }

            if ($oldSourceCount <= $largeGroupThreshold) {
                $newSourceMemberData = $this->calculateNewMemberData(
                    $oldSourceMemberIds,
                    $oldSourceMemberNames,
                    $userIds,
                    [],
                    []
                );
                $newSourceMemberIds = $newSourceMemberData['ids'];
                $newSourceMemberNames = $newSourceMemberData['names'];
            } else {
                $newSourceCount = $oldSourceCount - count($userIds);
                $newSourceMemberIds = ["[{$newSourceCount} members]"];
                $newSourceMemberNames = ["[{$newSourceCount} members]"];
            }

            $this->dispatchMemberChangeAuditLog(
                $sourceGroup,
                $oldSourceMemberNames,
                $oldSourceMemberIds,
                $newSourceMemberNames,
                $newSourceMemberIds
            );

            if (! empty($usersToAdd)) {
                $addedUserNames = $usersData->filter(fn ($user) => in_array($user->id, $usersToAdd))
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->toArray();

                if ($oldTargetCount <= $largeGroupThreshold) {
                    $newTargetMemberData = $this->calculateNewMemberData(
                        $oldTargetMemberIds,
                        $oldTargetMemberNames,
                        [],
                        $usersToAdd,
                        $addedUserNames
                    );
                    $newTargetMemberIds = $newTargetMemberData['ids'];
                    $newTargetMemberNames = $newTargetMemberData['names'];
                } else {
                    $newTargetCount = $oldTargetCount + count($usersToAdd);
                    $newTargetMemberIds = ["[{$newTargetCount} members]"];
                    $newTargetMemberNames = ["[{$newTargetCount} members]"];
                }

                $this->dispatchMemberChangeAuditLog(
                    $targetGroup,
                    $oldTargetMemberNames,
                    $oldTargetMemberIds,
                    $newTargetMemberNames,
                    $newTargetMemberIds
                );
            }

            $this->cacheService->clearForMembershipChange($userIds);

            $count = count($userIds);
            $alreadyInTarget = count($userIds) - count($usersToAdd);

            $message = "{$count} member".($count === 1 ? '' : 's').' transferred successfully.';
            if ($alreadyInTarget > 0) {
                $message .= " {$alreadyInTarget} ".($alreadyInTarget === 1 ? 'was' : 'were').' already in the target group.';
            }

            return [
                'count' => $count,
                'alreadyInTarget' => $alreadyInTarget,
                'message' => $message,
            ];
        });
    }

    /**
     * Get member data (IDs and names) for a group efficiently.
     *
     * This method fetches all users belonging to a given group using chunking
     * to prevent memory exhaustion with large groups. It extracts and sorts
     * their IDs and names for audit logging purposes.
     *
     * @param  Group  $group  The group for which to retrieve member data
     * @return array{ids: array<int>, names: array<string>} An associative array
     *                                                      containing two keys: 'ids' (an array of sorted user IDs) and
     *                                                      'names' (an array of sorted user names)
     */
    private function getMemberData(Group $group): array
    {
        $ids = [];
        $names = [];

        $chunkSize = (int) settings('groups_member_data_chunk_size', 1000);
        $group->users()->select('users.id', 'users.name')->chunk($chunkSize, function ($members) use (&$ids, &$names) {
            foreach ($members as $member) {
                $ids[] = $member->id;
                $names[] = $member->name;
            }
        });

        sort($ids);
        sort($names);

        return [
            'ids' => array_values($ids),
            'names' => array_values($names),
        ];
    }

    /**
     * Calculate new member data by applying changes to old data.
     *
     * This avoids re-querying all members when we know what changed.
     *
     * @param  array<string>  $oldIds  Previous member IDs
     * @param  array<string>  $oldNames  Previous member names
     * @param  array<string>  $removedIds  User IDs being removed
     * @param  array<string>  $addedIds  User IDs being added
     * @param  array<string>  $addedNames  Names for added users (must match addedIds order)
     * @return array{ids: array<string>, names: array<string>} Updated member data
     */
    private function calculateNewMemberData(
        array $oldIds,
        array $oldNames,
        array $removedIds,
        array $addedIds,
        array $addedNames
    ): array {
        $newIds = array_values(array_diff($oldIds, $removedIds));
        $oldNamesMap = array_combine($oldIds, $oldNames);
        $newNames = [];
        foreach ($newIds as $id) {
            if (isset($oldNamesMap[$id])) {
                $newNames[] = $oldNamesMap[$id];
            }
        }

        $newIds = array_merge($newIds, $addedIds);
        $newNames = array_merge($newNames, $addedNames);

        sort($newIds);
        sort($newNames);

        return [
            'ids' => array_values($newIds),
            'names' => array_values($newNames),
        ];
    }

    /**
     * Prepare audit log data for a group's member changes.
     *
     * Handles large groups efficiently by using count placeholders instead of loading all members.
     *
     * @param  Group  $group  The group to prepare data for
     * @param  int  $largeGroupThreshold  Threshold for using count placeholders
     * @return array{oldIds: array<int|string>, oldNames: array<string>, oldCount: int} Audit log data
     */
    private function prepareMemberAuditLogData(Group $group, ?int $largeGroupThreshold = null): array
    {
        $largeGroupThreshold = $largeGroupThreshold ?? (int) settings('groups_large_group_threshold', 100);

        $oldCount = DB::table('groups_users')
            ->where('group_id', $group->id)
            ->count();

        if ($oldCount <= $largeGroupThreshold) {
            $oldMemberData = $this->getMemberData($group);
            $oldMemberIds = $oldMemberData['ids'];
            $oldMemberNames = $oldMemberData['names'];
        } else {
            $oldMemberIds = ["[{$oldCount} members]"];
            $oldMemberNames = ["[{$oldCount} members]"];
        }

        return [
            'oldIds' => $oldMemberIds,
            'oldNames' => $oldMemberNames,
            'oldCount' => $oldCount,
        ];
    }

    /**
     * Dispatch audit log for group member changes.
     *
     * @param  Group  $group  The group that was modified
     * @param  array<string>  $oldMemberNames  Previous member names
     * @param  array<int|string>  $oldMemberIds  Previous member IDs
     * @param  array<string>  $newMemberNames  New member names
     * @param  array<int|string>  $newMemberIds  New member IDs
     * @return void
     */
    private function dispatchMemberChangeAuditLog(
        Group $group,
        array $oldMemberNames,
        array $oldMemberIds,
        array $newMemberNames,
        array $newMemberIds
    ): void {
        CreateAuditLogJob::dispatch(
            event: AuditLogEvent::UPDATED,
            model: $group,
            oldValues: [
                'members' => $oldMemberNames,
                'member_ids' => $oldMemberIds,
            ],
            newValues: [
                'members' => $newMemberNames,
                'member_ids' => $newMemberIds,
            ],
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent(),
            tags: 'group,members'
        );
    }
}
