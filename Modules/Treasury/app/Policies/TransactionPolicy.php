<?php

/**
 * Transaction Policy
 *
 * Authorization policy for transaction operations. Enforces permission-based
 * access control and user ownership checks for viewing, creating, updating,
 * and deleting transactions. Super Admin bypass is handled via trait.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Treasury\Models\Transaction;

/**
 * Class TransactionPolicy
 *
 * Defines authorization rules for transaction CRUD operations.
 */
class TransactionPolicy
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
        return $user->hasPermissionTo('treasuries.transaction.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  Transaction  $transaction
     * @return bool
     */
    public function view(User $user, Transaction $transaction): bool
    {
        if ($transaction->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.transaction.view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.transaction.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  Transaction  $transaction
     * @return bool
     */
    public function update(User $user, Transaction $transaction): bool
    {
        if ($transaction->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.transaction.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @param  Transaction  $transaction
     * @return bool
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        if ($transaction->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.transaction.delete');
    }
}
