<?php

/**
 * Groups Permission Registrar.
 *
 * Registers permission definitions for the Groups module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Groups\Services;

use App\Services\Registry\PermissionRegistry;

/**
 * Handles permission registration for the Groups module.
 */
class GroupsPermissionRegistrar
{
    /**
     * Register all permissions required by the Groups module.
     *
     * @param  PermissionRegistry  $registry
     * @return void
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
            'dashboard.view',
            'preferences.view',
        ], [
            'Administrator' => ['group.*', 'dashboard.view', 'preferences.view'],
        ]);
    }
}
