<?php

namespace Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\App\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\Permission;
use Modules\Core\App\Models\Role;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionCacheService;
use Modules\Core\App\Services\PermissionService;
use Modules\Groups\App\Services\GroupCacheService;
use Modules\Groups\Http\Requests\AddMembersRequest;
use Modules\Groups\Http\Requests\RemoveMembersRequest;
use Modules\Groups\Http\Requests\StoreGroupRequest;
use Modules\Groups\Http\Requests\TransferMembersRequest;
use Modules\Groups\Http\Requests\UpdateGroupRequest;
use Modules\Groups\Models\Group;

class GroupsController extends Controller
{
    use SyncsRelationshipsWithAuditLog;

    public function __construct(
        private PermissionService $permissionService,
        private GroupCacheService $cacheService,
        private PermissionCacheService $permissionCacheService
    ) {}

    /**
     * Display a listing of groups.
     */
    public function index(DatatableQueryService $datatableService): Response
    {
        $this->authorize('viewAny', Group::class);

        $defaultPerPage = 20;

        $groups = $datatableService->build(
            Group::withCount(['users', 'permissions'])
                ->with(['roles' => function ($query) {
                    $query->select('roles.id', 'roles.name');
                }]),
            [
                'searchFields' => ['name', 'slug', 'description'],
                'allowedSorts' => ['name', 'slug', 'created_at', 'updated_at'],
                'defaultSort' => 'created_at',
                'defaultDirection' => 'desc',
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        foreach ($groups->items() as $group) {
            $group->setAttribute('roles', $group->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ])->values());
        }

        return Inertia::render('Modules::Groups/Groups/Index', [
            'groups' => $groups,
            'defaultPerPage' => $defaultPerPage,
        ]);
    }

    /**
     * Display the specified group.
     */
    public function show(DatatableQueryService $datatableService, Group $group): Response
    {
        $this->authorize('view', $group);

        $group->load(['permissions', 'roles']);
        $groupedPermissions = $this->permissionService->groupByModule($group->permissions);

        $availableGroups = Group::where('id', '!=', $group->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaultPerPage = 10;

        $members = $datatableService->build(
            User::whereHas('groups', function ($query) use ($group) {
                $query->where('groups.id', $group->id);
            })->with(['avatar', 'roles']),
            [
                'searchFields' => ['name', 'email'],
                'allowedSorts' => ['name', 'email'],
                'defaultSort' => 'name',
                'defaultDirection' => 'asc',
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        return Inertia::render('Modules::Groups/Groups/Show', [
            'group' => $group,
            'groupedPermissions' => $groupedPermissions,
            'availableGroups' => $availableGroups,
            'members' => $members,
            'defaultPerPage' => $defaultPerPage,
        ]);
    }

    /**
     * Get paginated members for a group.
     *
     * Returns paginated, searchable, and sortable list of group members.
     * Used for server-side datatable operations. Only updates the members
     * and defaultPerPage props to minimize data transfer.
     */
    public function members(DatatableQueryService $datatableService, Group $group): Response
    {
        $this->authorize('view', $group);

        $defaultPerPage = 10;

        $members = $datatableService->build(
            User::whereHas('groups', function ($query) use ($group) {
                $query->where('groups.id', $group->id);
            })->with(['avatar', 'roles']),
            [
                'searchFields' => ['name', 'email'],
                'allowedSorts' => ['name', 'email'],
                'defaultSort' => 'name',
                'defaultDirection' => 'asc',
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        return Inertia::render('Modules::Groups/Groups/Show', [
            'members' => $members,
            'defaultPerPage' => $defaultPerPage,
        ], [
            'only' => ['members', 'defaultPerPage'],
        ]);
    }

    /**
     * Get paginated available users that can be added to a group.
     *
     * Returns users that are not already members of the group, with server-side
     * search and pagination. Used for the "Add Members" dialog.
     */
    public function availableUsers(DatatableQueryService $datatableService, Group $group): Response
    {
        $this->authorize('view', $group);

        $defaultPerPage = 20;

        $availableUsers = $datatableService->build(
            User::whereDoesntHave('groups', function ($query) use ($group) {
                $query->where('groups.id', $group->id);
            })->with(['avatar', 'roles']),
            [
                'searchFields' => ['name', 'email'],
                'allowedSorts' => ['name', 'email'],
                'defaultSort' => 'name',
                'defaultDirection' => 'asc',
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        return Inertia::render('Modules::Groups/Groups/Show', [
            'availableUsers' => $availableUsers,
            'defaultPerPage' => $defaultPerPage,
        ], [
            'only' => ['availableUsers', 'defaultPerPage'],
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create(): Response
    {
        $this->authorize('create', Group::class);

        $roles = Role::with('permissions')
            ->where('name', '!=', Roles::SUPER_ADMIN)
            ->get();
        $permissions = Permission::all();
        $groupedPermissions = $this->permissionService->groupByModule($permissions);

        return Inertia::render('Modules::Groups/Groups/Create', [
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->authorize('create', Group::class);

        $group = Group::create([
            'name' => $request->name,
            'slug' => $request->slug ?? \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
        ]);

        if ($request->has('members') && is_array($request->members)) {
            $this->syncGroupMembers($group, $request->members);
        }

        if ($request->has('permissions') && is_array($request->permissions)) {
            $this->syncGroupPermissions($group, $request->permissions);
        }

        if ($request->has('roles') && is_array($request->roles)) {
            $this->syncGroupRoles($group, $request->roles);
        }

        return redirect()->route('groups.groups.index')
            ->with('success', 'Group created successfully.');
    }

    /**
     * Show the form for editing the specified group.
     */
    public function edit(Group $group): Response
    {
        $this->authorize('update', $group);

        $group->load(['permissions', 'roles.permissions']);
        $roles = Role::with('permissions')
            ->where('name', '!=', Roles::SUPER_ADMIN)
            ->get();
        $permissions = Permission::all();
        $groupedPermissions = $this->permissionService->groupByModule($permissions);

        return Inertia::render('Modules::Groups/Groups/Edit', [
            'group' => $group,
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Update the specified group in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $group->update([
            'name' => $request->name,
            'slug' => $request->slug ?? \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
        ]);

        // Note: Members are managed on the Show page, not in the edit form
        if ($request->has('permissions') && is_array($request->permissions)) {
            $this->syncGroupPermissions($group, $request->permissions);
        }

        if ($request->has('roles') && is_array($request->roles)) {
            $this->syncGroupRoles($group, $request->roles);
        }

        return redirect()->route('groups.groups.show', $group)
            ->with('success', 'Group updated successfully.');
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        $userCount = $group->users()->count();

        if ($userCount > 0) {
            return redirect()->route('groups.groups.show', $group)
                ->with('error', "Cannot delete group. It has {$userCount} ".
                    ($userCount === 1 ? 'member' : 'members').
                    '. Please transfer or remove all members first.');
        }

        $group->delete();

        return redirect()->route('groups.groups.index')
            ->with('success', 'Group deleted successfully.');
    }

    /**
     * Sync group members and handle related operations.
     *
     * Synchronizes the group's member list, logs changes to the audit log,
     * and clears permission caches for all affected users.
     *
     * @param  Group  $group  The group to sync members for
     * @param  array<int|string>  $newMemberIds  Array of user IDs to assign to the group
     * @return void
     */
    private function syncGroupMembers(Group $group, array $newMemberIds): void
    {
        $currentMemberIds = $group->users()->pluck('users.id')->toArray();
        $allAffectedUserIds = array_unique(array_merge($currentMemberIds, $newMemberIds));

        $this->syncRelationshipWithAuditLog(
            model: $group,
            relationshipName: 'users',
            newIds: $newMemberIds,
            relatedModelClass: User::class,
            relationshipDisplayName: 'members'
        );

        $this->cacheService->clearForMembershipChange($allAffectedUserIds);
    }

    /**
     * Sync group permissions and handle related operations.
     *
     * Synchronizes the group's direct permission assignments, logs changes
     * to the audit log, and clears permission caches for all affected users.
     *
     * @param  Group  $group  The group to sync permissions for
     * @param  array<int|string>  $newPermissionIds  Array of permission IDs to assign to the group
     * @return void
     */
    private function syncGroupPermissions(Group $group, array $newPermissionIds): void
    {
        $this->syncRelationshipWithAuditLog(
            model: $group,
            relationshipName: 'permissions',
            newIds: $newPermissionIds,
            relatedModelClass: Permission::class,
            relationshipDisplayName: 'permissions'
        );

        $userIds = $group->users()->pluck('users.id')->toArray();

        if (! empty($userIds)) {
            $this->cacheService->clearForPermissionChange($userIds);
        } else {
            $this->permissionCacheService->clear();
        }
    }

    /**
     * Sync group roles and handle related operations.
     *
     * Synchronizes the group's role assignments, automatically filters out
     * the Super Admin role (which cannot be assigned to groups), logs changes
     * to the audit log, and clears permission caches for all affected users.
     *
     * @param  Group  $group  The group to sync roles for
     * @param  array<int|string>  $newRoleIds  Array of role IDs to assign to the group
     * @return void
     */
    private function syncGroupRoles(Group $group, array $newRoleIds): void
    {
        static $superAdminRoleId = null;
        if ($superAdminRoleId === null) {
            $superAdminRoleId = Role::where('name', Roles::SUPER_ADMIN)->value('id');
        }

        if ($superAdminRoleId) {
            $newRoleIds = array_filter(
                $newRoleIds,
                fn ($roleId) => (string) $roleId !== (string) $superAdminRoleId
            );
        }

        $this->syncRelationshipWithAuditLog(
            model: $group,
            relationshipName: 'roles',
            newIds: $newRoleIds,
            relatedModelClass: Role::class,
            relationshipDisplayName: 'roles'
        );

        $userIds = $group->users()->pluck('users.id')->toArray();

        if (! empty($userIds)) {
            $this->cacheService->clearForRoleChange($userIds);
        } else {
            $this->permissionCacheService->clear();
        }
    }

    /**
     * Add members to the specified group.
     *
     * Bulk adds multiple users to a group. Skips users that are already members.
     * All operations are performed within a database transaction to ensure data integrity.
     * Changes are logged to the audit log and permission caches are cleared.
     */
    public function addMembers(AddMembersRequest $request, Group $group): RedirectResponse
    {
        return DB::transaction(function () use ($request, $group) {
            $userIds = $request->user_ids;
            $largeGroupThreshold = config('groups.large_group_threshold', 100);

            /**
             * Step 1: Identify users already in group to avoid duplicate additions.
             * Only checks the provided user IDs, not all group members, for efficiency.
             */
            $existingInGroup = DB::table('group_user')
                ->where('group_id', $group->id)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $newUserIds = array_diff($userIds, $existingInGroup);

            if (empty($newUserIds)) {
                return redirect()
                    ->route('groups.groups.show', $group)
                    ->with('info', 'All selected users are already members of this group.');
            }

            /**
             * Step 2: Fetch names for new members only.
             * Limits query to users being added, not all group members.
             */
            $newUsersData = User::whereIn('id', $newUserIds)
                ->select('id', 'name')
                ->get();
            $newUserNames = $newUsersData->pluck('name')->sort()->values()->toArray();

            /**
             * Step 3: Prepare audit log data with optimized handling for large groups.
             * Skips loading full member data for groups exceeding threshold to prevent memory issues.
             */
            $auditData = $this->prepareMemberAuditLogData($group, $largeGroupThreshold);
            $oldMemberIds = $auditData['oldIds'];
            $oldMemberNames = $auditData['oldNames'];
            $oldCount = $auditData['oldCount'];

            /**
             * Step 4: Insert new memberships using raw SQL for better performance.
             * Bulk insert is faster than Eloquent's attach() method for multiple records.
             */
            $insertData = array_map(fn ($userId) => [
                'group_id' => $group->id,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $newUserIds);

            DB::table('group_user')->insert($insertData);

            /**
             * Step 5: Calculate new member data without additional database queries.
             * Merges old and new data in memory for efficiency.
             */
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

            /**
             * Step 6: Dispatch audit log asynchronously to avoid blocking the response.
             * Audit log creation happens in background via queued job.
             */
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

            return redirect()
                ->route('groups.groups.show', $group)
                ->with('success', $message);
        });
    }

    /**
     * Remove members from the specified group.
     *
     * Bulk removes multiple users from a group. Only removes users that are actually members.
     * All operations are performed within a database transaction to ensure data integrity.
     * Changes are logged to the audit log and permission caches are cleared.
     */
    public function removeMembers(RemoveMembersRequest $request, Group $group): RedirectResponse
    {
        return DB::transaction(function () use ($request, $group) {
            $userIds = $request->user_ids;
            $largeGroupThreshold = config('groups.large_group_threshold', 100);

            // Step 1: Check which users are actually in the group (fast - only check these users, not all members)
            $existingInGroup = DB::table('group_user')
                ->where('group_id', $group->id)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $usersToRemove = array_intersect($userIds, $existingInGroup);

            if (empty($usersToRemove)) {
                return redirect()
                    ->route('groups.groups.show', $group)
                    ->with('info', 'None of the selected users are members of this group.');
            }

            /**
             * Step 2: Prepare audit log data with optimized handling for large groups.
             * Skips loading full member data for groups exceeding threshold to prevent memory issues.
             */
            $auditData = $this->prepareMemberAuditLogData($group, $largeGroupThreshold);
            $oldMemberIds = $auditData['oldIds'];
            $oldMemberNames = $auditData['oldNames'];
            $oldCount = $auditData['oldCount'];

            /**
             * Step 3: Remove memberships using raw SQL for better performance.
             * Bulk delete is faster than Eloquent's detach() method for multiple records.
             */
            DB::table('group_user')
                ->where('group_id', $group->id)
                ->whereIn('user_id', $usersToRemove)
                ->delete();

            /**
             * Step 4: Calculate new member data without additional database queries.
             * Removes deleted users from old data in memory for efficiency.
             */
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

            /**
             * Step 5: Dispatch audit log asynchronously to avoid blocking the response.
             * Audit log creation happens in background via queued job.
             */
            $this->dispatchMemberChangeAuditLog(
                $group,
                $oldMemberNames,
                $oldMemberIds,
                $newMemberNames,
                $newMemberIds
            );

            $this->cacheService->clearForMembershipChange($usersToRemove);

            $count = count($usersToRemove);

            return redirect()
                ->route('groups.groups.show', $group)
                ->with('success', "{$count} member".($count === 1 ? '' : 's').' removed successfully.');
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

        // Use chunking to prevent memory exhaustion with large groups
        $chunkSize = config('groups.member_data_chunk_size', 1000);
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
     * Get member data for specific user IDs only (optimized for transfers).
     *
     * This method is much faster than getMemberData when you only need
     * data for a small subset of users, avoiding loading all group members.
     *
     * @param  Group  $group  The group to check
     * @param  array<string>  $userIds  Specific user IDs to get data for
     * @return array{ids: array<string>, names: array<string>} An associative array
     */
    private function getMemberDataForUsers(Group $group, array $userIds): array
    {
        if (empty($userIds)) {
            return ['ids' => [], 'names' => []];
        }

        $members = $group->users()
            ->whereIn('users.id', $userIds)
            ->select('users.id', 'users.name')
            ->get();

        $ids = $members->pluck('id')->sort()->values()->toArray();
        $names = $members->pluck('name')->sort()->values()->toArray();

        return [
            'ids' => $ids,
            'names' => $names,
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
        // Remove transferred users
        $newIds = array_values(array_diff($oldIds, $removedIds));
        $oldNamesMap = array_combine($oldIds, $oldNames);
        $newNames = [];
        foreach ($newIds as $id) {
            if (isset($oldNamesMap[$id])) {
                $newNames[] = $oldNamesMap[$id];
            }
        }

        // Add new users
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
        $largeGroupThreshold = $largeGroupThreshold ?? config('groups.large_group_threshold', 100);

        $oldCount = DB::table('group_user')
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
        \Modules\AuditLog\App\Jobs\CreateAuditLogJob::dispatch(
            event: 'updated',
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
            userAgent: request()?->userAgent()
        );
    }

    /**
     * Transfer multiple members from the current group to another group.
     *
     * Removes users from the source group and adds them to the target group.
     * Users already in the target group are skipped. All operations are performed
     * within a database transaction to ensure data integrity. Changes are logged
     * to the audit log for both groups and permission caches are cleared.
     */
    public function transferMembers(TransferMembersRequest $request, Group $group): RedirectResponse
    {
        return DB::transaction(function () use ($request, $group) {
            $userIds = $request->user_ids;
            $targetGroupId = $request->target_group_id;
            $targetGroup = Group::findOrFail($targetGroupId);

            $largeGroupThreshold = config('groups.large_group_threshold', 100);

            /**
             * Step 1: Fetch user data for users being transferred in a single query.
             * Limits data retrieval to only the affected users for efficiency.
             */
            $usersData = DB::table('users')
                ->select('id', 'name')
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy('id');

            /**
             * Step 2: Identify users already in target group to avoid duplicate additions.
             * Uses efficient EXISTS query instead of loading all target group members.
             */
            $existingInTarget = DB::table('group_user')
                ->where('group_id', $targetGroup->id)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $usersToAdd = array_diff($userIds, $existingInTarget);

            /**
             * Step 3: Prepare audit log data for both source and target groups.
             * Optimized handling skips loading full member data for large groups to prevent memory issues.
             */
            $sourceAuditData = $this->prepareMemberAuditLogData($group, $largeGroupThreshold);
            $oldSourceMemberIds = $sourceAuditData['oldIds'];
            $oldSourceMemberNames = $sourceAuditData['oldNames'];
            $oldSourceCount = $sourceAuditData['oldCount'];

            $targetAuditData = $this->prepareMemberAuditLogData($targetGroup, $largeGroupThreshold);
            $oldTargetMemberIds = $targetAuditData['oldIds'];
            $oldTargetMemberNames = $targetAuditData['oldNames'];
            $oldTargetCount = $targetAuditData['oldCount'];

            /**
             * Step 4: Perform transfer using raw SQL for better performance.
             * Bulk delete and insert operations are faster than Eloquent's detach/attach methods.
             */
            DB::table('group_user')
                ->where('group_id', $group->id)
                ->whereIn('user_id', $userIds)
                ->delete();

            if (! empty($usersToAdd)) {
                $insertData = array_map(fn ($userId) => [
                    'group_id' => $targetGroup->id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $usersToAdd);

                DB::table('group_user')->insert($insertData);
            }

            /**
             * Step 5: Calculate new member data for source group without additional queries.
             * Removes transferred users from old data in memory for efficiency.
             */
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

            /**
             * Step 6: Dispatch audit log for source group asynchronously.
             * Audit log creation happens in background via queued job to avoid blocking response.
             */
            $this->dispatchMemberChangeAuditLog(
                $group,
                $oldSourceMemberNames,
                $oldSourceMemberIds,
                $newSourceMemberNames,
                $newSourceMemberIds
            );

            if (! empty($usersToAdd)) {
                $addedUserNames = $usersData->whereIn('id', $usersToAdd)
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

                /**
                 * Step 7: Dispatch audit log for target group asynchronously.
                 * Audit log creation happens in background via queued job to avoid blocking response.
                 */
                $this->dispatchMemberChangeAuditLog(
                    $targetGroup,
                    $oldTargetMemberNames,
                    $oldTargetMemberIds,
                    $newTargetMemberNames,
                    $newTargetMemberIds
                );
            }

            /**
             * Step 8: Clear permission and group caches for affected users.
             * Ensures cached permissions are refreshed after membership changes.
             */
            $this->cacheService->clearForMembershipChange($userIds);

            $count = count($userIds);
            $alreadyInTarget = count($userIds) - count($usersToAdd);

            $message = "{$count} member".($count === 1 ? '' : 's').' transferred successfully.';
            if ($alreadyInTarget > 0) {
                $message .= " {$alreadyInTarget} ".($alreadyInTarget === 1 ? 'was' : 'were').' already in the target group.';
            }

            return redirect()
                ->route('groups.groups.show', $group)
                ->with('success', $message);
        });
    }
}
