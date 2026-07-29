<?php

/**
 * Bot Menu Registrar
 *
 * Registers sidebar menu items for the Bot module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Services;

use App\Services\Registry\MenuRegistry;

class BotMenuRegistrar
{
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Automation',
            label: 'Bot',
            route: 'bot.index',
            icon: 'Bot',
            order: 60,
            permission: 'bot.bridge.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Bot Dashboard',
            route: 'bot.dashboard',
            icon: 'LayoutDashboard',
            order: 95,
            permission: 'bot.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
