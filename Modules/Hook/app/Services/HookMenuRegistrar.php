<?php

/**
 * Hook Menu Registrar
 *
 * Registers sidebar menu items for the Hook module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Class HookMenuRegistrar
 */
class HookMenuRegistrar
{
    /**
     * @param  MenuRegistry  $menuRegistry
     * @param  string  $moduleName
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Developer',
            label: 'Hook',
            route: 'hook.index',
            icon: 'Webhook',
            order: 20,
            permission: 'hook.inbound.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Hook Dashboard',
            route: 'hook.dashboard',
            icon: 'LayoutDashboard',
            order: 90,
            permission: 'hook.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
