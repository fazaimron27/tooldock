<?php

namespace Modules\Settings\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Settings module.
 */
class SettingsMenuRegistrar
{
    /**
     * Register all menu items for the Settings module.
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'System',
            label: 'Settings',
            route: 'settings.index',
            icon: 'Settings',
            order: 30,
            permission: 'settings.config.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Settings Dashboard',
            route: 'settings.dashboard',
            icon: 'LayoutDashboard',
            order: 70,
            permission: 'settings.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
