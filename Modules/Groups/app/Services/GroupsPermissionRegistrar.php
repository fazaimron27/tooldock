<?php

namespace Modules\Groups\Services;

use App\Services\Registry\PermissionRegistry;

/**
 * Handles permission registration for the Groups module.
 */
class GroupsPermissionRegistrar
{
    /**
     * Register default permissions for the Groups module.
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('groups', [
            'group.view',
            'group.create',
            'group.edit',
            'group.delete',
            'group.add-members',
            'group.remove-members',
            'group.transfer-members',
            'groups.dashboard.view',
        ], [
            'Administrator' => ['group.*', 'groups.dashboard.view'],
        ]);
    }
}
