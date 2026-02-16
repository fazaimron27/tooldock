<?php

/**
 * Wallet Policy
 *
 * Authorization policy for wallet operations. Enforces permission-based
 * access control and user ownership checks for viewing, creating,
 * updating, and deleting wallets. Super Admin bypass is handled via trait.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Treasury\Models\Wallet;

/**
 * Class WalletPolicy
 *
 * Defines authorization rules for wallet CRUD operations.
 */
class WalletPolicy
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
        return $user->hasPermissionTo('treasuries.wallet.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  Wallet  $wallet
     * @return bool
     */
    public function view(User $user, Wallet $wallet): bool
    {
        if ($wallet->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.wallet.view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.wallet.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  Wallet  $wallet
     * @return bool
     */
    public function update(User $user, Wallet $wallet): bool
    {
        if ($wallet->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.wallet.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @param  Wallet  $wallet
     * @return bool
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        if ($wallet->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.wallet.delete');
    }
}
