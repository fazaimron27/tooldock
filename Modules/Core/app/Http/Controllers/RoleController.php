<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\Constants\Roles as RoleConstants;
use Modules\Core\Http\Requests\StoreRoleRequest;
use Modules\Core\Http\Requests\UpdateRoleRequest;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Services\PermissionCacheService;
use Modules\Core\Services\PermissionService;
use Modules\Groups\Services\GroupPermissionCacheService;

class RoleController extends Controller
{
    use SyncsRelationshipsWithAuditLog;

    public function __construct(
        private PermissionService $permissionService,
        private MenuRegistry $menuRegistry,
        private PermissionCacheService $permissionCacheService,
        private GroupPermissionCacheService $groupPermissionCacheService
    ) {}

    /**
     * Display a listing of roles.
     */
    public function index(DatatableQueryService $datatableService): Response
    {
        $this->authorize('viewAny', Role::class);

        $defaultPerPage = 20;

        $roles = $datatableService->build(
            Role::with('permissions'),
            [
                'searchFields' => ['name'],
                'allowedSorts' => ['name', 'created_at', 'updated_at'],
                'defaultSort' => 'created_at',
                'defaultDirection' => 'desc',
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        $roleIds = array_column($roles->items(), 'id');
        if (! empty($roleIds)) {
            $groupsByRole = \Modules\Groups\Models\Group::query()
                ->join('groups_roles', 'groups.id', '=', 'groups_roles.group_id')
                ->whereIn('groups_roles.role_id', $roleIds)
                ->select('groups.id', 'groups.name', 'groups_roles.role_id')
                ->get()
                ->groupBy('role_id')
                ->map(fn ($groups) => $groups->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                ])->values());

            foreach ($roles->items() as $role) {
                $role->setAttribute('groups', $groupsByRole->get($role->id, []));
            }
        } else {
            foreach ($roles->items() as $role) {
                $role->setAttribute('groups', []);
            }
        }

        return Inertia::render('Modules::Core/Roles/Index', [
            'roles' => $roles,
            'defaultPerPage' => $defaultPerPage,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): Response
    {
        $this->authorize('create', Role::class);

        $permissions = cache()->remember('core:permissions:all', 3600, fn () => Permission::all());
        $groupedPermissions = $this->permissionService->groupByModule($permissions);

        return Inertia::render('Modules::Core/Roles/Create', [
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        if ($request->name === RoleConstants::SUPER_ADMIN) {
            return redirect()->route('core.roles.index')
                ->with('error', 'Cannot create Super Admin role. This is a critical system role required for system security.');
        }

        $role = Role::create([
            'name' => $request->name,
        ]);

        if ($request->has('permissions') && is_array($request->permissions)) {
            $this->syncRolePermissions($role, $request->permissions);
        }

        return redirect()->route('core.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        $role->load('permissions');
        $permissions = cache()->remember('core:permissions:all', 3600, fn () => Permission::all());
        $groupedPermissions = $this->permissionService->groupByModule($permissions);

        return Inertia::render('Modules::Core/Roles/Edit', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $originalName = $role->name;

        if ($originalName === RoleConstants::SUPER_ADMIN && $request->name !== RoleConstants::SUPER_ADMIN) {
            return redirect()->route('core.roles.edit', $role)
                ->with('error', 'Cannot rename Super Admin role. The role name is required for system security.');
        }

        $role->update([
            'name' => $request->name,
        ]);

        if ($originalName !== RoleConstants::SUPER_ADMIN) {
            $permissions = $request->has('permissions') && is_array($request->permissions)
                ? $request->permissions
                : [];

            $this->syncRolePermissions($role, $permissions);
        }

        return redirect()->route('core.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->name === RoleConstants::SUPER_ADMIN) {
            return redirect()->route('core.roles.index')
                ->with('error', 'Cannot delete Super Admin role. This is a critical system role required for system security.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('core.roles.index')
                ->with('error', 'Cannot delete role that is assigned to users.');
        }

        $groupCount = DB::table('groups_roles')
            ->where('role_id', $role->id)
            ->count();

        if ($groupCount > 0) {
            return redirect()->route('core.roles.index')
                ->with('error', "Cannot delete role. It is used as a base role in {$groupCount} ".
                    ($groupCount === 1 ? 'group' : 'groups').
                    '. Please remove the role from all groups first.');
        }

        $userIds = $role->users()->pluck('users.id')->toArray();
        $role->delete();

        if (! empty($userIds)) {
            foreach ($userIds as $userId) {
                $this->menuRegistry->clearCacheForUser($userId);
            }
        }

        return redirect()->route('core.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Sync permissions for a role and log changes to audit log.
     *
     * @param  Role  $role
     * @param  array<int|string>  $newPermissionIds
     * @return void
     */
    private function syncRolePermissions(Role $role, array $newPermissionIds): void
    {
        $this->syncRelationshipWithAuditLog(
            model: $role,
            relationshipName: 'permissions',
            newIds: $newPermissionIds,
            relatedModelClass: Permission::class,
            relationshipDisplayName: 'permissions'
        );

        // Get users directly assigned to this role
        $directUserIds = $role->users()->pluck('users.id')->toArray();

        // Get users in groups that have this role assigned
        $groupUserIds = DB::table('groups_users')
            ->join('groups_roles', 'groups_users.group_id', '=', 'groups_roles.group_id')
            ->where('groups_roles.role_id', $role->id)
            ->pluck('groups_users.user_id')
            ->toArray();

        // Combine and deduplicate user IDs
        $allAffectedUserIds = array_unique(array_merge($directUserIds, $groupUserIds));

        // Clear both menu and group permission cache for all affected users
        // and dispatch signal event for real-time frontend refresh
        $signalRegistry = app(SignalHandlerRegistry::class);

        // Pre-fetch all affected users to avoid N+1 queries
        $affectedUsers = \Modules\Core\Models\User::whereIn('id', $allAffectedUserIds)->get()->keyBy('id');

        foreach ($allAffectedUserIds as $userId) {
            $this->menuRegistry->clearCacheForUser($userId);
            $this->groupPermissionCacheService->clearForUser($userId);

            // Dispatch signal event for broadcast notification (inbox + toast + page reload)
            $user = $affectedUsers->get($userId);
            if ($user) {
                $signalRegistry->dispatch('role.permissions.synced', [
                    'user' => $user,
                    'role' => $role,
                ]);
            }
        }

        // Warm the permission cache after sync since model observer doesn't fire
        // for pivot table changes (Spatie clears cache but we need to warm it)
        $this->permissionCacheService->warm();
    }
}
