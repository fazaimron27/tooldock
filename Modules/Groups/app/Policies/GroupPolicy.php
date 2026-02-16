<?php

/**
 * Group Policy.
 *
 * Defines authorization rules for group-related actions.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Groups\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Groups\Models\Group;

/**
 * Policy for Group model authorization.
 */
class GroupPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('groups.group.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  Group  $group
     * @return bool
     */
    public function view(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('groups.group.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  Group  $group
     * @return bool
     */
    public function update(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @param  Group  $group
     * @return bool
     */
    public function delete(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.delete');
    }

    /**
     * Determine whether the user can add members to the group.
     *
     * @param  User  $user
     * @param  Group  $group
     * @return bool
     */
    public function addMembers(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.add-members');
    }

    /**
     * Determine whether the user can remove members from the group.
     *
     * @param  User  $user
     * @param  Group  $group
     * @return bool
     */
    public function removeMembers(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.remove-members');
    }

    /**
     * Determine whether the user can transfer members between groups.
     *
     * @param  User  $user
     * @param  Group  $group
     * @return bool
     */
    public function transferMembers(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.transfer-members');
    }
}
