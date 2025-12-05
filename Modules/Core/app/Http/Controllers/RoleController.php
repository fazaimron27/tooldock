<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use App\Services\Registry\MenuRegistry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\App\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\App\Constants\Roles as RoleConstants;
use Modules\Core\App\Services\PermissionService;
use Modules\Core\Http\Requests\StoreRoleRequest;
use Modules\Core\Http\Requests\UpdateRoleRequest;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use SyncsRelationshipsWithAuditLog;

    public function __construct(
        private PermissionService $permissionService,
        private MenuRegistry $menuRegistry
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

        $permissions = Permission::all();
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
        $permissions = Permission::all();
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

        $userIds = $role->users()->pluck('users.id')->toArray();
        if (! empty($userIds)) {
            foreach ($userIds as $userId) {
                $this->menuRegistry->clearCacheForUser($userId);
            }
        }
    }
}
