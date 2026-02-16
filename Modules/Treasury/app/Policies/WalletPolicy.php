<?php

namespace Modules\Treasury\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Treasury\Models\Wallet;

class WalletPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.wallet.view');
    }

    /**
     * Determine whether the user can view the model.
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
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('treasuries.wallet.create');
    }

    /**
     * Determine whether the user can update the model.
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
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        if ($wallet->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('treasuries.wallet.delete');
    }
}
