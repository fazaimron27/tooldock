<?php

namespace Modules\Groups\Services;

use Illuminate\Support\Facades\Log;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\Role;
use Modules\Groups\Models\Group;

/**
 * Handles role assignment to groups.
 */
class GroupsRoleService
{
    /**
     * Ensure the Guest role is attached to the Guest group.
     *
     * This should be called after roles and groups are seeded.
     */
    public function ensureGuestRoleAttached(): void
    {
        $guestGroup = Group::where('name', 'Guest')->first();
        $guestRole = Role::where('name', Roles::GUEST)->first();

        if (! $guestGroup) {
            Log::warning('GroupsRoleService: Guest group not found, skipping role attachment');

            return;
        }

        if (! $guestRole) {
            Log::warning('GroupsRoleService: Guest role not found, skipping role attachment');

            return;
        }

        if (! $guestGroup->roles()->where('roles.id', $guestRole->id)->exists()) {
            $guestGroup->assignRole($guestRole);
            Log::info('GroupsRoleService: Guest role attached to Guest group');
        }
    }
}
