<?php

namespace Modules\Treasury\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Treasury\Models\Budget;

class BudgetPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.budget.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Budget $budget): bool
    {
        if ($budget->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.budget.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.budget.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Budget $budget): bool
    {
        if ($budget->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.budget.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Budget $budget): bool
    {
        if ($budget->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.budget.delete');
    }
}
