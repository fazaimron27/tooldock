<?php

/**
 * Nucleus Permission Registrar
 *
 * Registers permissions for the Nucleus module and maps them to roles.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Class NucleusPermissionRegistrar
 *
 * @see PermissionRegistry
 */
class NucleusPermissionRegistrar
{
    /**
     * Register permissions and role mappings for the Nucleus module.
     *
     * @param  PermissionRegistry  $registry  The central permission registry
     * @return void
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('nucleus', [
            'snippet.view',
            'snippet.create',
            'snippet.delete',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'snippet.view',
                'snippet.create',
                'snippet.delete',
            ],
            RoleConstants::MANAGER => [
                'snippet.view',
                'snippet.create',
                'snippet.delete',
            ],
        ]);
    }
}
