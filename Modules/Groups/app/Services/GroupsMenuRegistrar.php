<?php

/**
 * Groups Menu Registrar.
 *
 * Registers navigation menu items for the Groups module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Groups\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Groups module.
 */
class GroupsMenuRegistrar
{
    /**
     * Register navigation menu items for the Groups module.
     *
     * @param  MenuRegistry  $menuRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'System',
            label: 'Groups',
            route: 'groups.groups.index',
            icon: 'UserPlus',
            order: 20,
            permission: 'groups.group.view',
            parentKey: 'core.user-management',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Groups Dashboard',
            route: 'groups.dashboard',
            icon: 'LayoutDashboard',
            order: 20,
            permission: 'groups.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
