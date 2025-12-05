<?php

namespace Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\App\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\App\Constants\Roles;
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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            Group::withCount(['users', 'permissions']),
            [
                'searchFields' => ['name', 'slug', 'description'],
                'allowedSorts' => ['name', 'slug', 'created_at', 'updated_at'],
                'defaultSort' => 'created_at',
                'defaultDirection' => 'desc',
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        Collection::make($groups->items())->load(['roles' => function ($query) {
            $query->select('roles.id', 'roles.name');
        }]);

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
    public function show(Group $group): Response
    {
        $this->authorize('view', $group);

        $group->load(['users.avatar', 'users.roles', 'permissions', 'roles']);
        $groupedPermissions = $this->permissionService->groupByModule($group->permissions);

        $availableGroups = Group::where('id', '!=', $group->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $allUsers = User::select('id', 'name', 'email')
            ->with(['avatar', 'roles'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Modules::Groups/Groups/Show', [
            'group' => $group,
            'groupedPermissions' => $groupedPermissions,
            'availableGroups' => $availableGroups,
            'allUsers' => $allUsers,
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create(): Response
    {
        $this->authorize('create', Group::class);

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        $roles = Role::with('permissions')
            ->where('name', '!=', Roles::SUPER_ADMIN)
            ->get();
        $permissions = Permission::all();
        $groupedPermissions = $this->permissionService->groupByModule($permissions);

        return Inertia::render('Modules::Groups/Groups/Create', [
            'users' => $users,
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
                fn ($roleId) => (int) $roleId !== $superAdminRoleId
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

            $oldMemberData = $this->getMemberData($group);
            $currentMemberIds = $oldMemberData['ids'];

            $newUserIds = array_diff($userIds, $currentMemberIds);

            if (empty($newUserIds)) {
                return redirect()
                    ->route('groups.groups.show', $group)
                    ->with('info', 'All selected users are already members of this group.');
            }

            $group->users()->attach($newUserIds);

            $group->load('users');
            $newMemberData = $this->getMemberData($group);
            $newMemberIds = $newMemberData['ids'];
            $newMemberNames = $newMemberData['names'];

            \Modules\AuditLog\App\Jobs\CreateAuditLogJob::dispatch(
                event: 'updated',
                model: $group,
                oldValues: [
                    'members' => $oldMemberData['names'],
                    'member_ids' => $oldMemberData['ids'],
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

            $oldMemberData = $this->getMemberData($group);
            $currentMemberIds = $oldMemberData['ids'];

            $usersToRemove = array_intersect($userIds, $currentMemberIds);

            if (empty($usersToRemove)) {
                return redirect()
                    ->route('groups.groups.show', $group)
                    ->with('info', 'None of the selected users are members of this group.');
            }

            $group->users()->detach($usersToRemove);

            $group->load('users');
            $newMemberData = $this->getMemberData($group);
            $newMemberIds = $newMemberData['ids'];
            $newMemberNames = $newMemberData['names'];

            \Modules\AuditLog\App\Jobs\CreateAuditLogJob::dispatch(
                event: 'updated',
                model: $group,
                oldValues: [
                    'members' => $oldMemberData['names'],
                    'member_ids' => $oldMemberData['ids'],
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
     * This method fetches all users belonging to a given group in a single database query,
     * then extracts and sorts their IDs and names. This optimizes performance by
     * reducing the number of database interactions, especially when audit logging
     * requires both old and new states of group members.
     *
     * @param  Group  $group  The group for which to retrieve member data
     * @return array{ids: array<int>, names: array<string>} An associative array
     *                                                      containing two keys: 'ids' (an array of sorted user IDs) and
     *                                                      'names' (an array of sorted user names)
     */
    private function getMemberData(Group $group): array
    {
        $members = $group->users()->select('users.id', 'users.name')->get();

        return [
            'ids' => $members->pluck('id')->sort()->values()->toArray(),
            'names' => $members->pluck('name')->sort()->values()->toArray(),
        ];
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

            $oldMemberData = $this->getMemberData($group);
            $oldMemberIds = $oldMemberData['ids'];
            $oldMemberNames = $oldMemberData['names'];

            $targetMemberData = $this->getMemberData($targetGroup);
            $targetGroupMemberIds = $targetMemberData['ids'];
            $usersToAdd = array_diff($userIds, $targetGroupMemberIds);

            $group->users()->detach($userIds);

            if (! empty($usersToAdd)) {
                $targetGroup->users()->attach($usersToAdd);
                $targetGroup->load('users');
            }

            $group->load('users');
            $newMemberData = $this->getMemberData($group);
            $newMemberIds = $newMemberData['ids'];
            $newMemberNames = $newMemberData['names'];

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

            if (! empty($usersToAdd)) {
                $targetNewMemberData = $this->getMemberData($targetGroup);
                $targetNewMemberIds = $targetNewMemberData['ids'];
                $targetNewMemberNames = $targetNewMemberData['names'];

                \Modules\AuditLog\App\Jobs\CreateAuditLogJob::dispatch(
                    event: 'updated',
                    model: $targetGroup,
                    oldValues: [
                        'members' => $targetMemberData['names'],
                        'member_ids' => $targetMemberData['ids'],
                    ],
                    newValues: [
                        'members' => $targetNewMemberNames,
                        'member_ids' => $targetNewMemberIds,
                    ],
                    userId: Auth::id(),
                    url: request()?->url(),
                    ipAddress: request()?->ip(),
                    userAgent: request()?->userAgent()
                );
            }

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
