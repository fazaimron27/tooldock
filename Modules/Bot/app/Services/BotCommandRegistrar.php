<?php

/**
 * Bot Command Registrar
 *
 * Registers Command Palette commands for the Bot module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Services;

use App\Services\Registry\CommandRegistry;

class BotCommandRegistrar
{
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'Automation', [
            [
                'label' => 'Bot',
                'route' => 'bot.index',
                'icon' => 'Bot',
                'permission' => 'bot.bridge.view',
                'keywords' => ['bot', 'telegram', 'discord', 'chat', 'automation'],
                'order' => 10,
            ],
            [
                'label' => 'Bot Messages',
                'route' => 'bot.messages.index',
                'icon' => 'MessageCircle',
                'permission' => 'bot.bridge.view',
                'keywords' => ['bot', 'messages', 'log', 'delivery'],
                'order' => 20,
            ],
            [
                'label' => 'Bot Dashboard',
                'route' => 'bot.dashboard',
                'icon' => 'LayoutDashboard',
                'permission' => 'bot.dashboard.view',
                'keywords' => ['bot', 'dashboard', 'stats'],
                'order' => 30,
            ],
        ]);
    }
}
