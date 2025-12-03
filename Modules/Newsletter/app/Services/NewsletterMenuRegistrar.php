<?php

namespace Modules\Newsletter\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Newsletter module.
 */
class NewsletterMenuRegistrar
{
    /**
     * Register all menu items for the Newsletter module.
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Content',
            label: 'Newsletter',
            route: 'newsletter.index',
            icon: 'Send',
            order: 20,
            permission: 'newsletter.campaigns.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Newsletter Dashboard',
            route: 'newsletter.dashboard',
            icon: 'LayoutDashboard',
            order: 50,
            permission: 'newsletter.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
