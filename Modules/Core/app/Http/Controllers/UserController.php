<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\App\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\App\Models\User;
use Modules\Core\Http\Requests\StoreUserRequest;
use Modules\Core\Http\Requests\UpdateUserRequest;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use SyncsRelationshipsWithAuditLog;

    /**
     * Display a paginated listing of users.
     */
    public function index(DatatableQueryService $datatableService): Response
    {
        $this->authorize('viewAny', User::class);

        $defaultPerPage = 20;

        $users = $datatableService->build(
            User::with('roles'),
            [
                'searchFields' => ['name', 'email'],
                'allowedSorts' => ['name', 'email', 'created_at', 'updated_at'],
                'defaultSort' => 'created_at',
                'defaultDirection' => 'desc',
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        return Inertia::render('Modules::Core/Users/Index', [
            'users' => $users,
            'defaultPerPage' => $defaultPerPage,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        $roles = Role::all();

        return Inertia::render('Modules::Core/Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->has('roles') && is_array($request->roles)) {
            $this->syncUserRoles($user, $request->roles);
        }

        return redirect()->route('core.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles');
        $roles = Role::all();

        return Inertia::render('Modules::Core/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->has('roles') && is_array($request->roles)) {
            $this->syncUserRoles($user, $request->roles);
        }

        return redirect()->route('core.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === request()->user()->id) {
            return redirect()->route('core.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('core.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Sync roles for a user and log changes to audit log.
     *
     * @param  User  $user
     * @param  array<int|string>  $newRoleIds
     * @return void
     */
    private function syncUserRoles(User $user, array $newRoleIds): void
    {
        $this->syncRelationshipWithAuditLog(
            model: $user,
            relationshipName: 'roles',
            newIds: $newRoleIds,
            relatedModelClass: Role::class,
            relationshipDisplayName: 'roles'
        );
    }
}
