<?php

/**
 * Role Policy.
 *
 * Authorization policy for role management operations
 * including view, create, update, and delete.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;

/**
 * Authorization policy for role management.
 *
 * Controls access to role CRUD operations based on
 * the user's assigned permissions.
 */
class RolePolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user  The authenticated user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user  The authenticated user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role to update
     * @return bool
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role to delete
     * @return bool
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }
}
