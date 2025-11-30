<?php

namespace Modules\Core\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasSuperAdminBypass;

class UserPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('core.users.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('core.users.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Prevent non-Super Admin users from deleting Super Admin users
        if ($model->hasRole(Roles::SUPER_ADMIN) && ! $user->hasRole(Roles::SUPER_ADMIN)) {
            return false;
        }

        return $user->hasPermissionTo('core.users.delete');
    }
}
