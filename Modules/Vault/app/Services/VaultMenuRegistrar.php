<?php

namespace Modules\Vault\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Vault module.
 */
class VaultMenuRegistrar
{
    /**
     * Register all menu items for the Vault module.
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Utilities',
            label: 'Vault',
            route: 'vault.index',
            icon: 'ShieldCheck',
            order: 10,
            permission: 'vaults.vault.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Vault Dashboard',
            route: 'vault.dashboard',
            icon: 'LayoutDashboard',
            order: 40,
            permission: 'vaults.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
