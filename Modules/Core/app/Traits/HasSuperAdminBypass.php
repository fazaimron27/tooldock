<?php

namespace Modules\Core\Traits;

use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;

/**
 * Trait for policies that need Super Admin bypass functionality.
 *
 * Provides a reusable `before()` method that grants Super Admin
 * users access to all policy actions.
 */
trait HasSuperAdminBypass
{
    /**
     * Perform pre-authorization checks.
     *
     * Grants Super Admin role access to all actions.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(Roles::SUPER_ADMIN)) {
            return true;
        }

        return null;
    }
}
