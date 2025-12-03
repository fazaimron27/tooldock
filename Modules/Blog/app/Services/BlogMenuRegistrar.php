<?php

namespace Modules\Blog\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Blog module.
 */
class BlogMenuRegistrar
{
    /**
     * Register all menu items for the Blog module.
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Content',
            label: 'Blog',
            route: 'blog.index',
            icon: 'FileText',
            order: 10,
            permission: 'blog.posts.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Blog Dashboard',
            route: 'blog.dashboard',
            icon: 'LayoutDashboard',
            order: 20,
            permission: 'blog.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
