<?php

/**
 * Categories Menu Registrar.
 *
 * Registers sidebar menu items and dashboard navigation entries
 * for the Categories module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Categories\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Categories module.
 */
class CategoriesMenuRegistrar
{
    /**
     * Register all menu items for the Categories module.
     *
     * @param  MenuRegistry  $menuRegistry  The menu registry
     * @param  string  $moduleName  The module name
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Master Data',
            label: 'Categories',
            route: 'categories.index',
            icon: 'Tag',
            order: 30,
            permission: 'categories.category.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Categories Dashboard',
            route: 'categories.dashboard',
            icon: 'LayoutDashboard',
            order: 30,
            permission: 'categories.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
