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
 * @see \App\Services\Registry\MenuRegistry
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
    }
}
