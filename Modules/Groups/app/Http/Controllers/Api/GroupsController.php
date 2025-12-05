<?php

namespace Modules\Groups\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionCacheService;
use Modules\Groups\App\Services\GroupCacheService;
use Modules\Groups\Models\Group;

class GroupsController extends Controller
{
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
     * Store a newly created group.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Group::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:groups,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:groups,slug'],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:'.(config('permission.table_names.users') ?? 'users').',id'],
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

        if (isset($validated['permissions'])) {
            $this->syncGroupPermissions($group, $validated['permissions']);
        }

        $group->load(['users', 'permissions']);

        return response()->json($group, 201);
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $group->load(['users', 'permissions']);

        return response()->json($group);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, Group $group): JsonResponse
    {
        $this->authorize('update', $group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:groups,name,'.$group->id],
            'slug' => ['nullable', 'string', 'max:255', 'unique:groups,slug,'.$group->id],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:'.(config('permission.table_names.users') ?? 'users').',id'],
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

        if (isset($validated['permissions'])) {
            $this->syncGroupPermissions($group, $validated['permissions']);
        }

        $group->load(['users', 'permissions']);

        return response()->json($group);
    }

    /**
     * Remove the specified group.
     */
    public function destroy(Group $group): JsonResponse
    {
        $this->authorize('delete', $group);

        $userIds = $group->users()->pluck('users.id')->toArray();
        $group->delete();

        if (! empty($userIds)) {
            $this->cacheService->clearForGroupDeletion($userIds);
        }

        return response()->json(['message' => 'Group deleted successfully'], 200);
    }

    /**
     * Bulk assign users to groups.
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
            foreach ($users as $user) {
                if (! $group->users()->where('users.id', $user->id)->exists()) {
                    $group->users()->attach($user->id);
                    $allAffectedUserIds[] = $user->id;
                }
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
     * Bulk remove users from groups.
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
            $group->users()->detach($validated['user_ids']);
            $allAffectedUserIds = array_merge($allAffectedUserIds, $validated['user_ids']);
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
     * Sync members for a group and clear caches.
     *
     * @param  Group  $group
     * @param  array<int|string>  $newMemberIds
     * @return void
     */
    private function syncGroupMembers(Group $group, array $newMemberIds): void
    {
        $currentMemberIds = $group->users()->pluck('users.id')->toArray();
        $allAffectedUserIds = array_unique(array_merge($currentMemberIds, $newMemberIds));

        $group->users()->sync($newMemberIds);

        $this->cacheService->clearForMembershipChange($allAffectedUserIds);
    }

    /**
     * Sync permissions for a group and clear caches.
     *
     * @param  Group  $group
     * @param  array<int|string>  $newPermissionIds
     * @return void
     */
    private function syncGroupPermissions(Group $group, array $newPermissionIds): void
    {
        $group->permissions()->sync($newPermissionIds);

        $userIds = $group->users()->pluck('users.id')->toArray();

        if (! empty($userIds)) {
            $this->cacheService->clearForPermissionChange($userIds);
        } else {
            $this->permissionCacheService->clear();
        }
    }
}
