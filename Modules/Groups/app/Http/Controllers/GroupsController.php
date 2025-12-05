<?php

namespace Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\App\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionCacheService;
use Modules\Core\App\Services\PermissionService;
use Modules\Groups\App\Services\GroupCacheService;
use Modules\Groups\Http\Requests\StoreGroupRequest;
use Modules\Groups\Http\Requests\UpdateGroupRequest;
use Modules\Groups\Models\Group;
use Spatie\Permission\Models\Permission;

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

        $group->load(['users.avatar', 'permissions']);
        $groupedPermissions = $this->permissionService->groupByModule($group->permissions);

        return Inertia::render('Modules::Groups/Groups/Show', [
            'group' => $group,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create(): Response
    {
        $this->authorize('create', Group::class);

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        $permissions = Permission::all();
        $groupedPermissions = $this->permissionService->groupByModule($permissions);

        return Inertia::render('Modules::Groups/Groups/Create', [
            'users' => $users,
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

        return redirect()->route('groups.groups.index')
            ->with('success', 'Group created successfully.');
    }

    /**
     * Show the form for editing the specified group.
     */
    public function edit(Group $group): Response
    {
        $this->authorize('update', $group);

        $group->load(['users', 'permissions']);
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        $permissions = Permission::all();
        $groupedPermissions = $this->permissionService->groupByModule($permissions);

        return Inertia::render('Modules::Groups/Groups/Edit', [
            'group' => $group,
            'users' => $users,
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

        return redirect()->route('groups.groups.index')
            ->with('success', 'Group updated successfully.');
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        $group->delete();

        return redirect()->route('groups.groups.index')
            ->with('success', 'Group deleted successfully.');
    }

    /**
     * Sync members for a group and log changes to audit log.
     *
     * @param  Group  $group
     * @param  array<int|string>  $newMemberIds
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
     * Sync permissions for a group and log changes to audit log.
     *
     * @param  Group  $group
     * @param  array<int|string>  $newPermissionIds
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
}
