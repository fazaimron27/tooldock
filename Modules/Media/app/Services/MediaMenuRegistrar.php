<?php

/**
 * Media Menu Registrar.
 *
 * Registers navigation menu items for the Media module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Media\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Media module.
 */
class MediaMenuRegistrar
{
    /**
     * Register all menu items for the Media module.
     *
     * @param  MenuRegistry  $menuRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'System',
            label: 'Media',
            route: 'media.index',
            icon: 'Image',
            order: 40,
            permission: 'media.files.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Media Dashboard',
            route: 'media.dashboard',
            icon: 'LayoutDashboard',
            order: 40,
            permission: 'media.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
