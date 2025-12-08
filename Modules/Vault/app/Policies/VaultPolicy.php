<?php

namespace Modules\Vault\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Vault\Models\Vault;

class VaultPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('vaults.vault.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vault $vault): bool
    {
        // Strict ownership check - users can only view their own vaults
        if ($vault->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('vaults.vault.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('vaults.vault.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vault $vault): bool
    {
        // Strict ownership check - users can only update their own vaults
        if ($vault->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('vaults.vault.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vault $vault): bool
    {
        // Strict ownership check - users can only delete their own vaults
        if ($vault->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('vaults.vault.delete');
    }
}
