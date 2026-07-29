<?php

/**
 * Folio Menu Registrar
 *
 * Registers sidebar menu items for the Folio module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Class FolioMenuRegistrar
 *
 * Adds Folio link to the application sidebar menu.
 *
 * @see MenuRegistry
 */
class FolioMenuRegistrar
{
    /**
     * Register all menu items for the Folio module.
     *
     * @param  MenuRegistry  $menuRegistry  The central menu registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Utilities',
            label: 'Folio',
            route: 'folio.index',
            icon: 'FileUser',
            order: 15,
            permission: 'folio.folio.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Folio Dashboard',
            route: 'folio.dashboard',
            icon: 'LayoutDashboard',
            order: 100,
            permission: 'folio.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
