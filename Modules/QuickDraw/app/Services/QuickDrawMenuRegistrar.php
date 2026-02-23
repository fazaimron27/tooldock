<?php

/**
 * QuickDraw Menu Registrar
 *
 * Registers sidebar menu items for the QuickDraw module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\QuickDraw\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Class QuickDrawMenuRegistrar
 *
 * Adds QuickDraw link to the application sidebar menu.
 *
 * @see \App\Services\Registry\MenuRegistry
 */
class QuickDrawMenuRegistrar
{
    /**
     * Register all menu items for the QuickDraw module.
     *
     * @param  MenuRegistry  $menuRegistry  The central menu registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Utilities',
            label: 'QuickDraw',
            route: 'quickdraw.index',
            icon: 'PenTool',
            order: 10,
            permission: 'quickdraw.draw.view',
            parentKey: null,
            module: $moduleName
        );
    }
}
