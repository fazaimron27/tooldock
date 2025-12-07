<?php

namespace Modules\Core\App\Traits;

use Modules\Core\App\Constants\Roles;

trait ChecksGuestUser
{
    /**
     * Check if user is Guest-only (only has Guest group).
     *
     * A user is considered Guest-only if:
     * - They are not Super Admin
     * - They have no roles assigned directly to them
     * - Their groups have no roles (or only have "Guest" role)
     * - They only have the Guest group (or no groups)
     * - They have no non-guest groups
     *
     * Note: We check group membership rather than permissions because
     * the Guest group may have basic permissions (like file upload) that
     * don't grant real system access.
     *
     * @param  \Modules\Core\App\Models\User  $user
     * @return bool
     */
    protected function isGuestOnly($user): bool
    {
        if ($user->hasRole(Roles::SUPER_ADMIN)) {
            return false;
        }

        if ($user->roles()->exists()) {
            return false;
        }

        $hasNonGuestGroup = $user->groups()
            ->where('slug', '!=', 'guest')
            ->exists();

        if ($hasNonGuestGroup) {
            return false;
        }

        $hasNonGuestRole = $user->groups()
            ->whereHas('roles', function ($query) {
                $query->where('name', '!=', Roles::GUEST);
            })
            ->exists();

        if ($hasNonGuestRole) {
            return false;
        }

        return true;
    }
}
