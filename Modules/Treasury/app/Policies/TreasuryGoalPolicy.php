<?php

/**
 * Treasury Goal Policy
 *
 * Authorization policy for savings goal operations. Enforces permission-based
 * access control and user ownership checks for viewing, creating, updating,
 * deleting, and allocating funds to goals. Super Admin bypass is handled via trait.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Treasury\Models\TreasuryGoal;

/**
 * Class TreasuryGoalPolicy
 *
 * Defines authorization rules for goal CRUD and fund allocation operations.
 */
class TreasuryGoalPolicy
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
        return $user->hasPermissionTo('treasuries.goal.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  TreasuryGoal  $goal
     * @return bool
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
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.goal.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  TreasuryGoal  $goal
     * @return bool
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
     *
     * @param  User  $user
     * @param  TreasuryGoal  $goal
     * @return bool
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
     *
     * @param  User  $user
     * @param  TreasuryGoal  $goal
     * @return bool
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
