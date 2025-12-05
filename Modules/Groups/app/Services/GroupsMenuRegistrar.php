<?php

namespace Modules\Groups\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Groups module.
 */
class GroupsMenuRegistrar
{
    /**
     * Register all menu items for the Groups module.
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'System',
            label: 'Groups',
            route: 'groups.groups.index',
            icon: 'UserPlus',
            order: 30,
            permission: 'groups.group.view',
            parentKey: 'core.user-management',
            module: $moduleName
        );
    }
}
