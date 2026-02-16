<?php

/**
 * Budget Policy
 *
 * Authorization policy for budget operations. Enforces permission-based
 * access control and user ownership checks for viewing, creating,
 * updating, and deleting budgets. Super Admin bypass is handled via trait.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Treasury\Models\Budget;

/**
 * Class BudgetPolicy
 *
 * Defines authorization rules for budget CRUD operations.
 */
class BudgetPolicy
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
        return $user->hasPermissionTo('treasuries.budget.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  Budget  $budget
     * @return bool
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
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.budget.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  Budget  $budget
     * @return bool
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
     *
     * @param  User  $user
     * @param  Budget  $budget
     * @return bool
     */
    public function delete(User $user, Budget $budget): bool
    {
        if ($budget->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.budget.delete');
    }
}
