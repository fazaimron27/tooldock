<?php

namespace Modules\Core\App\Traits;

use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;

/**
 * Trait for controllers that need to check Super Admin status.
 *
 * Provides a reusable method to check if the current user is a Super Admin.
 */
trait ChecksSuperAdmin
{
    /**
     * Check if the current user (or provided user) is a Super Admin.
     */
    protected function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? request()->user();

        return $user?->hasRole(Roles::SUPER_ADMIN) ?? false;
    }
}
