<?php

/**
 * Routine Menu Registrar
 *
 * Registers sidebar menu items for the Routine module under the Life OS group.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Class RoutineMenuRegistrar
 *
 * @see \App\Services\Registry\MenuRegistry
 */
class RoutineMenuRegistrar
{
    /**
     * Register menu items for the Routine module.
     *
     * @param  MenuRegistry  $menuRegistry  The central menu registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Life OS',
            label: 'Routine',
            route: 'routine.index',
            icon: 'Repeat',
            order: 20,
            permission: 'routines.routine.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Routine Dashboard',
            route: 'routine.dashboard',
            icon: 'LayoutDashboard',
            order: 80,
            permission: 'routines.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
