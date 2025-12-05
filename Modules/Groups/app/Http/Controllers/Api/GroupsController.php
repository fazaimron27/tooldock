<?php

namespace Modules\Groups\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\App\Jobs\CreateAuditLogJob;
use Modules\AuditLog\App\Traits\SyncsRelationshipsWithAuditLog;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionCacheService;
use Modules\Groups\App\Services\GroupCacheService;
use Modules\Groups\Models\Group;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GroupsController extends Controller
{
    use SyncsRelationshipsWithAuditLog;

    public function __construct(
        private GroupCacheService $cacheService,
        private PermissionCacheService $permissionCacheService
    ) {}

    /**
     * Display a listing of groups.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Group::class);

        $query = Group::withCount(['users', 'permissions']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $groups = $query->paginate($perPage);

        return response()->json($groups);
    }

    /**
     * Store a newly created group in storage.
     *
     * Validates input, creates the group, and syncs members, roles, and permissions.
     * Prevents assignment of the Super Admin role to groups.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Group::class);

        static $superAdminRoleId = null;
        if ($superAdminRoleId === null) {
            $superAdminRoleId = Role::where('name', Roles::SUPER_ADMIN)->value('id');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:groups,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:groups,slug'],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:'.(config('permission.table_names.users') ?? 'users').',id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($superAdminRoleId) {
                    if ($superAdminRoleId && (int) $value === $superAdminRoleId) {
                        $fail('The Super Admin role cannot be assigned to groups.');
                    }
                },
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['members'])) {
            $this->syncGroupMembers($group, $validated['members']);
        }

        if (isset($validated['roles'])) {
            $this->syncGroupRoles($group, $validated['roles']);
        }

        if (isset($validated['permissions'])) {
            $this->syncGroupPermissions($group, $validated['permissions']);
        }

        $group->load(['users', 'permissions', 'roles']);

        return response()->json($group, 201);
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $group->load(['users', 'permissions', 'roles']);

        return response()->json($group);
    }

    /**
     * Update the specified group in storage.
     *
     * Validates input, updates the group attributes, and syncs members, roles, and permissions.
     * Prevents assignment of the Super Admin role to groups.
     */
    public function update(Request $request, Group $group): JsonResponse
    {
        $this->authorize('update', $group);

        static $superAdminRoleId = null;
        if ($superAdminRoleId === null) {
            $superAdminRoleId = Role::where('name', Roles::SUPER_ADMIN)->value('id');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:groups,name,'.$group->id],
            'slug' => ['nullable', 'string', 'max:255', 'unique:groups,slug,'.$group->id],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:'.(config('permission.table_names.users') ?? 'users').',id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'exists:roles,id',
                function ($attribute, $value, $fail) use ($superAdminRoleId) {
                    if ($superAdminRoleId && (int) $value === $superAdminRoleId) {
                        $fail('The Super Admin role cannot be assigned to groups.');
                    }
                },
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $group->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['members'])) {
            $this->syncGroupMembers($group, $validated['members']);
        }

        if (isset($validated['roles'])) {
            $this->syncGroupRoles($group, $validated['roles']);
        }

        if (isset($validated['permissions'])) {
            $this->syncGroupPermissions($group, $validated['permissions']);
        }

        $group->load(['users', 'permissions', 'roles']);

        return response()->json($group);
    }

    /**
     * Remove the specified group from storage.
     *
     * Prevents deletion if the group has members. Users must be transferred
     * or removed before the group can be deleted.
     */
    public function destroy(Group $group): JsonResponse
    {
        $this->authorize('delete', $group);

        $userCount = $group->users()->count();

        if ($userCount > 0) {
            return response()->json([
                'message' => "Cannot delete group. It has {$userCount} ".
                    ($userCount === 1 ? 'member' : 'members').
                    '. Please transfer or remove all members first.',
            ], 422);
        }

        $group->delete();

        return response()->json(['message' => 'Group deleted successfully'], 200);
    }

    /**
     * Bulk assign users to multiple groups.
     *
     * Assigns the specified users to the specified groups. Only assigns users
     * that are not already members. Logs all changes to the audit log.
     */
    public function bulkAssignUsers(Request $request): JsonResponse
    {
        $this->authorize('update', Group::class);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:'.(config('permission.table_names.users') ?? 'users').',id'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['exists:groups,id'],
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();
        $groups = Group::whereIn('id', $validated['group_ids'])->get();

        $allAffectedUserIds = [];

        foreach ($groups as $group) {
            $oldMemberIds = $group->users()->pluck('users.id')->sort()->values()->toArray();
            $oldMemberNames = $group->users()->pluck('name')->sort()->values()->toArray();
            $addedUserIds = [];

            foreach ($users as $user) {
                if (! $group->users()->where('users.id', $user->id)->exists()) {
                    $group->users()->attach($user->id);
                    $addedUserIds[] = $user->id;
                    $allAffectedUserIds[] = $user->id;
                }
            }

            if (! empty($addedUserIds)) {
                $group->load('users');
                $newMemberIds = $group->users()->pluck('users.id')->sort()->values()->toArray();
                $newMemberNames = $group->users()->pluck('name')->sort()->values()->toArray();

                CreateAuditLogJob::dispatch(
                    event: 'updated',
                    model: $group,
                    oldValues: [
                        'members' => $oldMemberNames,
                        'members_ids' => $oldMemberIds,
                    ],
                    newValues: [
                        'members' => $newMemberNames,
                        'members_ids' => $newMemberIds,
                    ],
                    userId: Auth::id(),
                    url: $request->url(),
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent()
                );
            }
        }

        if (! empty($allAffectedUserIds)) {
            $this->cacheService->clearForMembershipChange(array_unique($allAffectedUserIds));
        }

        return response()->json([
            'message' => 'Users assigned to groups successfully',
            'affected_users' => count(array_unique($allAffectedUserIds)),
        ]);
    }

    /**
     * Bulk remove users from multiple groups.
     *
     * Removes the specified users from the specified groups. Logs all changes
     * to the audit log and clears relevant caches.
     */
    public function bulkRemoveUsers(Request $request): JsonResponse
    {
        $this->authorize('update', Group::class);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:'.(config('permission.table_names.users') ?? 'users').',id'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['exists:groups,id'],
        ]);

        $groups = Group::whereIn('id', $validated['group_ids'])->get();

        $allAffectedUserIds = [];

        foreach ($groups as $group) {
            $oldMemberIds = $group->users()->pluck('users.id')->sort()->values()->toArray();
            $oldMemberNames = $group->users()->pluck('name')->sort()->values()->toArray();
            $removedUserIds = array_intersect($oldMemberIds, $validated['user_ids']);

            $group->users()->detach($validated['user_ids']);
            $allAffectedUserIds = array_merge($allAffectedUserIds, $validated['user_ids']);

            if (! empty($removedUserIds)) {
                $group->load('users');
                $newMemberIds = $group->users()->pluck('users.id')->sort()->values()->toArray();
                $newMemberNames = $group->users()->pluck('name')->sort()->values()->toArray();

                CreateAuditLogJob::dispatch(
                    event: 'updated',
                    model: $group,
                    oldValues: [
                        'members' => $oldMemberNames,
                        'members_ids' => $oldMemberIds,
                    ],
                    newValues: [
                        'members' => $newMemberNames,
                        'members_ids' => $newMemberIds,
                    ],
                    userId: Auth::id(),
                    url: $request->url(),
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent()
                );
            }
        }

        if (! empty($allAffectedUserIds)) {
            $this->cacheService->clearForMembershipChange(array_unique($allAffectedUserIds));
        }

        return response()->json([
            'message' => 'Users removed from groups successfully',
            'affected_users' => count(array_unique($allAffectedUserIds)),
        ]);
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
}
