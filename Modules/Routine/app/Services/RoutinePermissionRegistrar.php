<?php

/**
 * Routine Permission Registrar
 *
 * Registers permissions for the Routine module and maps them to roles.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Class RoutinePermissionRegistrar
 *
 * @see \App\Services\Registry\PermissionRegistry
 */
class RoutinePermissionRegistrar
{
    /**
     * Register permissions and role mappings for the Routine module.
     *
     * @param  PermissionRegistry  $registry  The central permission registry
     * @return void
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('routines', [
            'dashboard.view',
            'routine.view',
            'routine.create',
            'routine.edit',
            'routine.delete',
            'preferences.view',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'routine.view',
                'routine.create',
                'routine.edit',
                'routine.delete',
                'preferences.view',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'routine.view',
                'routine.create',
                'routine.edit',
                'routine.delete',
                'preferences.view',
            ],
        ]);
    }
}
