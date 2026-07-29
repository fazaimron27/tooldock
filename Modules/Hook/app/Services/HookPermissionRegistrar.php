<?php

/**
 * Hook Permission Registrar
 *
 * Registers permissions for the Hook module and maps them to roles.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Class HookPermissionRegistrar
 *
 * @see PermissionRegistry
 */
class HookPermissionRegistrar
{
    /**
     * Register permissions and role mappings for the Hook module.
     *
     * @param  PermissionRegistry  $registry
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('hook', [
            'dashboard.view',
            'inbound.view',
            'inbound.create',
            'inbound.delete',
            'outbound.view',
            'outbound.create',
            'outbound.edit',
            'outbound.delete',
            'outbound.send',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'inbound.view',
                'inbound.create',
                'inbound.delete',
                'outbound.view',
                'outbound.create',
                'outbound.edit',
                'outbound.delete',
                'outbound.send',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'inbound.view',
                'inbound.create',
                'inbound.delete',
                'outbound.view',
                'outbound.create',
                'outbound.edit',
                'outbound.delete',
                'outbound.send',
            ],
        ]);
    }
}
