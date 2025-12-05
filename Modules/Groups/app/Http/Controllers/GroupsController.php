<?php

namespace Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\App\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionCacheService;
use Modules\Core\App\Services\PermissionService;
use Modules\Groups\App\Services\GroupCacheService;
use Modules\Groups\Http\Requests\StoreGroupRequest;
use Modules\Groups\Http\Requests\TransferUserRequest;
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

        $group->load(['users.avatar', 'permissions', 'roles']);
        $groupedPermissions = $this->permissionService->groupByModule($group->permissions);

        $availableGroups = Group::where('id', '!=', $group->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Modules::Groups/Groups/Show', [
            'group' => $group,
            'groupedPermissions' => $groupedPermissions,
            'availableGroups' => $availableGroups,
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

        $group->load(['users', 'permissions', 'roles.permissions']);
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        $roles = Role::with('permissions')
            ->where('name', '!=', Roles::SUPER_ADMIN)
            ->get();
        $permissions = Permission::all();
        $groupedPermissions = $this->permissionService->groupByModule($permissions);

        return Inertia::render('Modules::Groups/Groups/Edit', [
            'group' => $group,
            'users' => $users,
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
     * Transfer a user from the current group to another group.
     *
     * Removes the user from the current group and adds them to the target group.
     * Clears permission caches for the affected user.
     */
    public function transferUser(TransferUserRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $userId = $request->user_id;
        $targetGroupId = $request->target_group_id;

        $targetGroup = Group::findOrFail($targetGroupId);

        $group->users()->detach($userId);

        if (! $targetGroup->users()->where('users.id', $userId)->exists()) {
            $targetGroup->users()->attach($userId);
        }

        $this->cacheService->clearForMembershipChange([$userId]);

        return redirect()
            ->route('groups.groups.show', $group)
            ->with('success', 'User transferred successfully.');
    }
}
