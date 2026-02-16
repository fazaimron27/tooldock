<?php

/**
 * Vault Menu Registrar
 *
 * Registers sidebar menu items for the Vault module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Class VaultMenuRegistrar
 *
 * Adds vault index and dashboard links to the application sidebar menu.
 *
 * @see \App\Services\Registry\MenuRegistry
 */
class VaultMenuRegistrar
{
    /**
     * Register all menu items for the Vault module.
     *
     * @param  MenuRegistry  $menuRegistry  The central menu registry
     * @param  string  $moduleName  The module name identifier
     * @return void
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
            order: 80,
            permission: 'vaults.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
