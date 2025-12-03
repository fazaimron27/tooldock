<?php

namespace Modules\Core\App\Services;

use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\RoleRegistry;
use Modules\Core\App\Constants\Roles;

/**
 * Handles role and permission registration for the Core module.
 */
class CorePermissionRegistrar
{
    /**
     * Register default roles for the Core module.
     */
    public function registerRoles(RoleRegistry $registry): void
    {
        $registry->register('core', Roles::SUPER_ADMIN);
        $registry->register('core', Roles::ADMINISTRATOR);
        $registry->register('core', Roles::MANAGER);
        $registry->register('core', Roles::STAFF);
        $registry->register('core', Roles::AUDITOR);
    }

    /**
     * Register default permissions for the Core module.
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('core', [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.manage',
            'modules.manage',
        ], [
            Roles::ADMINISTRATOR => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'roles.manage',
                'modules.manage',
            ],
            Roles::MANAGER => [
                'dashboard.view',
                'users.view',
            ],
            Roles::STAFF => [
                'dashboard.view',
            ],
            Roles::AUDITOR => [
                'dashboard.view',
                'users.view',
            ],
        ]);
    }
}
