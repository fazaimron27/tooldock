<?php

namespace Modules\Vault\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Handles permission registration for the Vault module.
 */
class VaultPermissionRegistrar
{
    /**
     * Register default permissions for the Vault module.
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('vaults', [
            'dashboard.view',
            'vault.view',
            'vault.create',
            'vault.edit',
            'vault.delete',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'vault.*',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'vault.*',
            ],
        ]);
    }
}
