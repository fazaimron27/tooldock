<?php

namespace Modules\Groups\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasSuperAdminBypass;
use Modules\Groups\Models\Group;

class GroupPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('groups.group.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('groups.group.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.delete');
    }

    /**
     * Determine whether the user can add members to the group.
     */
    public function addMembers(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.add-members');
    }

    /**
     * Determine whether the user can remove members from the group.
     */
    public function removeMembers(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.remove-members');
    }

    /**
     * Determine whether the user can transfer members between groups.
     */
    public function transferMembers(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('groups.group.transfer-members');
    }
}
