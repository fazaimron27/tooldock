<?php

/**
 * Nucleus Menu Registrar
 *
 * Registers sidebar menu items for the Nucleus module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Class NucleusMenuRegistrar
 *
 * Adds the Nucleus JSON editor link to the application sidebar menu.
 *
 * @see MenuRegistry
 */
class NucleusMenuRegistrar
{
    /**
     * Register all menu items for the Nucleus module.
     *
     * @param  MenuRegistry  $menuRegistry  The central menu registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Developer',
            label: 'Nucleus',
            route: 'nucleus.index',
            icon: 'Braces',
            order: 10,
            permission: 'nucleus.snippet.view',
            parentKey: null,
            module: $moduleName
        );
    }
}
