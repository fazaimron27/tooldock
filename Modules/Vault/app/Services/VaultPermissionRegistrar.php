<?php

/**
 * Vault Permission Registrar
 *
 * Registers permission definitions and role mappings for the Vault module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Class VaultPermissionRegistrar
 *
 * Defines vault permissions (dashboard, CRUD, preferences) and maps
 * them to the Administrator and Manager roles.
 *
 * @see \App\Services\Registry\PermissionRegistry
 */
class VaultPermissionRegistrar
{
    /**
     * Register default permissions for the Vault module.
     *
     * @param  PermissionRegistry  $registry  The central permission registry
     * @return void
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('vaults', [
            'dashboard.view',
            'vault.view',
            'vault.create',
            'vault.edit',
            'vault.delete',
            'preferences.view',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'vault.*',
                'preferences.view',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'vault.*',
                'preferences.view',
            ],
        ]);
    }
}
