<?php

/**
 * User Controller
 *
 * Handles CRUD operations for user management by administrators.
 * Integrates with AuditLog for tracking and Signal for notifications.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use App\Services\Registry\MenuRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\Http\Requests\StoreUserRequest;
use Modules\Core\Http\Requests\UpdateUserRequest;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Signal\Traits\SendsSignalNotifications;

/**
 * Class UserController
 *
 * Provides administrative user management functionality including
 * listing, creating, editing, and deleting users. Sends notifications
 * when user roles are changed.
 *
 * @see \Modules\AuditLog\Traits\SyncsRelationshipsWithAuditLog For role sync logging
 * @see \Modules\Signal\Traits\SendsSignalNotifications For role change notifications
 */
class UserController extends Controller
{
    use SendsSignalNotifications;
    use SyncsRelationshipsWithAuditLog;

    /**
     * Create a new controller instance.
     *
     * @param  MenuRegistry  $menuRegistry  Menu registry for cache clearing
     * @return void
     */
    public function __construct(
        private MenuRegistry $menuRegistry
    ) {}

    /**
     * Display a paginated listing of users.
     *
     * Lists all users with their roles, avatars, and groups.
     * Supports search, sorting, and pagination.
     *
     * @param  DatatableQueryService  $datatableService  Datatable query builder
     * @return Response Inertia users index page
     */
    public function index(DatatableQueryService $datatableService): Response
    {
        $this->authorize('viewAny', User::class);

        $defaultPerPage = 20;

        $users = $datatableService->build(
            User::with([
                'roles' => function ($query) {
                    $query->select('id', 'name');
                },
                'avatar',
                'groups' => function ($query) {
                    $query->select('id', 'name');
                },
            ]),
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
     *
     * @return Response Inertia user creation form
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
     *
     * Creates a new user account and assigns roles if provided.
     *
     * @param  StoreUserRequest  $request  The validated user creation request
     * @return RedirectResponse Redirect to users index
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
     *
     * @param  User  $user  The user to edit
     * @return Response Inertia user edit form
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
     *
     * Updates user details and syncs roles if provided.
     *
     * @param  UpdateUserRequest  $request  The validated update request
     * @param  User  $user  The user to update
     * @return RedirectResponse Redirect to users index
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
     *
     * Prevents users from deleting their own account.
     *
     * @param  User  $user  The user to delete
     * @return RedirectResponse Redirect to users index
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
     * Syncs the user's roles with the provided role IDs, logs the
     * change to audit log, and sends a notification to the user
     * if their roles have changed.
     *
     * @param  User  $user  The user to update
     * @param  array<int|string>  $newRoleIds  Array of role IDs to assign
     * @return void
     */
    private function syncUserRoles(User $user, array $newRoleIds): void
    {
        $oldRoleNames = $user->roles->pluck('name')->toArray();

        $this->syncRelationshipWithAuditLog(
            model: $user,
            relationshipName: 'roles',
            newIds: $newRoleIds,
            relatedModelClass: Role::class,
            relationshipDisplayName: 'roles'
        );

        $this->menuRegistry->clearCacheForUser($user->id);

        $user->load('roles');
        $newRoleNames = $user->roles->pluck('name')->toArray();

        if ($oldRoleNames !== $newRoleNames) {
            $oldRolesText = empty($oldRoleNames) ? 'none' : implode(', ', $oldRoleNames);
            $newRolesText = empty($newRoleNames) ? 'none' : implode(', ', $newRoleNames);

            $this->signalInfo(
                $user,
                'Your Roles Changed',
                "An administrator changed your roles from [{$oldRolesText}] to [{$newRolesText}]. Your permissions may have changed.",
                route('dashboard'),
                'System',
                'system'
            );
        }
    }
}
