<?php

namespace Modules\Treasury\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Treasury\Models\TreasuryGoal;

class TreasuryGoalPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.goal.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TreasuryGoal $goal): bool
    {
        if ($goal->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.goal.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.goal.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TreasuryGoal $goal): bool
    {
        if ($goal->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.goal.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TreasuryGoal $goal): bool
    {
        if ($goal->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.goal.delete');
    }

    /**
     * Determine whether the user can allocate funds to the goal.
     */
    public function allocate(User $user, TreasuryGoal $goal): bool
    {
        if ($goal->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.goal.edit')
            && $user->hasPermissionTo('treasuries.transaction.create');
    }
}
