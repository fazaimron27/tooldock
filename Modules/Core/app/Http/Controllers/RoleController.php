<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\App\Constants\Roles as RoleConstants;
use Modules\Core\App\Services\PermissionService;
use Modules\Core\Http\Requests\StoreRoleRequest;
use Modules\Core\Http\Requests\UpdateRoleRequest;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
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
            $role->syncPermissions($request->permissions);
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
            if ($request->has('permissions') && is_array($request->permissions)) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->syncPermissions([]);
            }
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

        $role->delete();

        return redirect()->route('core.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
