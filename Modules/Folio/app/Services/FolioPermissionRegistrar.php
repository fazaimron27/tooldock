<?php

/**
 * Folio Permission Registrar
 *
 * Registers permissions for the Folio module and maps them to roles.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Class FolioPermissionRegistrar
 *
 * @see \App\Services\Registry\PermissionRegistry
 */
class FolioPermissionRegistrar
{
    /**
     * Register permissions and role mappings for the Folio module.
     *
     * @param  PermissionRegistry  $registry  The central permission registry
     * @return void
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('folio', [
            'folio.view',
            'folio.create',
            'folio.edit',
            'folio.delete',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'folio.view',
                'folio.create',
                'folio.edit',
                'folio.delete',
            ],
            RoleConstants::MANAGER => [
                'folio.view',
                'folio.create',
                'folio.edit',
                'folio.delete',
            ],
        ]);
    }
}
