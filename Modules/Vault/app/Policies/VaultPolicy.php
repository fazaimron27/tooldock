<?php

/**
 * Vault Policy
 *
 * Authorization policy for vault CRUD operations.
 * Enforces ownership checks and permission-based access control.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Vault\Models\Vault;

/**
 * Class VaultPolicy
 *
 * Defines authorization rules for vault items. Each action requires
 * the corresponding permission and, for item-level actions, ownership
 * verification (user_id match).
 *
 * @see \Modules\Vault\Models\Vault
 */
class VaultPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if the user has the view permission
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('vaults.vault.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user  The authenticated user
     * @param  Vault  $vault  The vault item to view
     * @return bool True if the user owns the vault and has view permission
     */
    public function view(User $user, Vault $vault): bool
    {
        if ($vault->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('vaults.vault.view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if the user has the create permission
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('vaults.vault.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user  The authenticated user
     * @param  Vault  $vault  The vault item to update
     * @return bool True if the user owns the vault and has edit permission
     */
    public function update(User $user, Vault $vault): bool
    {
        if ($vault->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('vaults.vault.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user  The authenticated user
     * @param  Vault  $vault  The vault item to delete
     * @return bool True if the user owns the vault and has delete permission
     */
    public function delete(User $user, Vault $vault): bool
    {
        if ($vault->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('vaults.vault.delete');
    }
}
