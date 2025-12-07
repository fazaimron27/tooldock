<?php

namespace Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\User;
use Modules\Groups\Http\Requests\AddMembersRequest;
use Modules\Groups\Http\Requests\RemoveMembersRequest;
use Modules\Groups\Http\Requests\TransferMembersRequest;
use Modules\Groups\Models\Group;
use Modules\Groups\Services\GroupMemberService;

/**
 * Controller for managing group members.
 *
 * Handles HTTP requests for adding, removing, transferring members,
 * and retrieving member/available user lists.
 */
class GroupMemberController extends Controller
{
    public function __construct(
        private GroupMemberService $memberService
    ) {}

    /**
     * Get paginated members of a group.
     *
     * Returns a paginated list of users who are members of the specified group.
     * Used for displaying the members table in the group detail page.
     */
    public function members(DatatableQueryService $datatableService, Group $group): Response
    {
        $this->authorize('view', $group);

        $defaultPerPage = (int) settings('groups_members_per_page', 10);
        $defaultSort = settings('groups_members_default_sort', 'name');
        $defaultDirection = settings('groups_members_default_sort_direction', 'asc');

        $members = $datatableService->build(
            User::whereHas('groups', function ($query) use ($group) {
                $query->where('groups.id', $group->id);
            })->with(['avatar', 'roles']),
            [
                'searchFields' => ['name', 'email'],
                'allowedSorts' => ['name', 'email'],
                'defaultSort' => $defaultSort,
                'defaultDirection' => $defaultDirection,
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        return Inertia::render('Modules::Groups/Groups/Show', [
            'members' => $members,
            'defaultPerPage' => $defaultPerPage,
        ], [
            'only' => ['members', 'defaultPerPage'],
        ]);
    }

    /**
     * Get paginated available users that can be added to a group.
     *
     * Returns users that are not already members of the group, with server-side
     * search and pagination. Used for the "Add Members" dialog.
     */
    public function availableUsers(DatatableQueryService $datatableService, Group $group): Response
    {
        $this->authorize('view', $group);

        $defaultPerPage = (int) settings('groups_available_users_per_page', 20);
        $defaultSort = settings('groups_members_default_sort', 'name');
        $defaultDirection = settings('groups_members_default_sort_direction', 'asc');

        $availableUsers = $datatableService->build(
            User::whereDoesntHave('groups', function ($query) use ($group) {
                $query->where('groups.id', $group->id);
            })->with(['avatar', 'roles']),
            [
                'searchFields' => ['name', 'email'],
                'allowedSorts' => ['name', 'email'],
                'defaultSort' => $defaultSort,
                'defaultDirection' => $defaultDirection,
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        return Inertia::render('Modules::Groups/Groups/Show', [
            'availableUsers' => $availableUsers,
            'defaultPerPage' => $defaultPerPage,
        ], [
            'only' => ['availableUsers', 'defaultPerPage'],
        ]);
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
        $this->authorize('update', $group);

        $result = $this->memberService->addMembers($group, $request->user_ids);

        if ($result['count'] === 0) {
            return redirect()
                ->route('groups.groups.show', $group)
                ->with('info', $result['message']);
        }

        return redirect()
            ->route('groups.groups.show', $group)
            ->with('success', $result['message']);
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
        $this->authorize('update', $group);

        $result = $this->memberService->removeMembers($group, $request->user_ids);

        if ($result['count'] === 0) {
            return redirect()
                ->route('groups.groups.show', $group)
                ->with('info', $result['message']);
        }

        return redirect()
            ->route('groups.groups.show', $group)
            ->with('success', $result['message']);
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
        $this->authorize('update', $group);

        $targetGroup = Group::findOrFail($request->target_group_id);
        $this->authorize('update', $targetGroup);

        $result = $this->memberService->transferMembers($group, $targetGroup, $request->user_ids);

        return redirect()
            ->route('groups.groups.show', $group)
            ->with('success', $result['message']);
    }
}
