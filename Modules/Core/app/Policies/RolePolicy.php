<?php

namespace Modules\Core\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasSuperAdminBypass;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }
}
