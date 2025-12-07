<?php

namespace Modules\Core\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Core module.
 */
class CoreMenuRegistrar
{
    /**
     * Register all menu items for the Core module.
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'User Management Dashboard',
            route: 'core.dashboard',
            icon: 'LayoutDashboard',
            order: 10,
            permission: 'core.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'System',
            label: 'User Management',
            route: 'core.user-management',
            icon: 'Users',
            order: 10,
            permission: null,
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'System',
            label: 'Users',
            route: 'core.users.index',
            icon: 'Users',
            order: 10,
            permission: 'core.users.view',
            parentKey: 'core.user-management',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'System',
            label: 'Roles',
            route: 'core.roles.index',
            icon: 'Shield',
            order: 20,
            permission: 'core.roles.manage',
            parentKey: 'core.user-management',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Platform',
            label: 'Modules',
            route: 'core.modules.index',
            icon: 'Package',
            order: 10,
            permission: 'core.modules.manage',
            parentKey: null,
            module: $moduleName
        );
    }
}
