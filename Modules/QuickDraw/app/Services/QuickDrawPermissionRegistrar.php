<?php

/**
 * QuickDraw Permission Registrar
 *
 * Registers permissions for the QuickDraw module and maps them to roles.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\QuickDraw\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Class QuickDrawPermissionRegistrar
 *
 * @see PermissionRegistry
 */
class QuickDrawPermissionRegistrar
{
    /**
     * Register permissions and role mappings for the QuickDraw module.
     *
     * @param  PermissionRegistry  $registry  The central permission registry
     * @return void
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('quickdraw', [
            'dashboard.view',
            'draw.view',
            'draw.create',
            'draw.edit',
            'draw.delete',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'draw.view',
                'draw.create',
                'draw.edit',
                'draw.delete',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'draw.view',
                'draw.create',
                'draw.edit',
                'draw.delete',
            ],
        ]);
    }
}
