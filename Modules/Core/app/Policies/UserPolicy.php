<?php

/**
 * User Policy.
 *
 * Authorization policy for user management operations including
 * view, create, update, delete, and super admin protections.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;

/**
 * Authorization policy for user management.
 *
 * Controls access to user CRUD operations and includes
 * Super Admin protections for delete operations.
 */
class UserPolicy
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
        return $user->hasPermissionTo('core.users.view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user  The authenticated user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.users.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user  The authenticated user
     * @param  User  $model  The user to update
     * @return bool
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('core.users.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Non-Super Admin users cannot delete Super Admin accounts.
     *
     * @param  User  $user  The authenticated user
     * @param  User  $model  The user to delete
     * @return bool
     */
    public function delete(User $user, User $model): bool
    {
        if ($model->hasRole(Roles::SUPER_ADMIN) && ! $user->hasRole(Roles::SUPER_ADMIN)) {
            return false;
        }

        return $user->hasPermissionTo('core.users.delete');
    }
}
