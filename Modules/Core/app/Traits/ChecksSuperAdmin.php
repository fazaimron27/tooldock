<?php

/**
 * Checks Super Admin Trait.
 *
 * Provides a reusable method to check if the current
 * or provided user has the Super Admin role.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Traits;

use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;

/**
 * Trait for controllers that need to check Super Admin status.
 *
 * Provides a reusable method to check if the current user is a Super Admin.
 */
trait ChecksSuperAdmin
{
    /**
     * Check if the current user (or provided user) is a Super Admin.
     *
     * @param  User|null  $user  The user to check, defaults to the authenticated user
     * @return bool
     */
    protected function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? request()->user();

        return $user?->hasRole(Roles::SUPER_ADMIN) ?? false;
    }
}
