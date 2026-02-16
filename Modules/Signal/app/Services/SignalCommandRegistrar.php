<?php

/**
 * Signal Command Registrar.
 *
 * Registers Command Palette commands for the Signal module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Signal\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Class SignalCommandRegistrar
 *
 * Registers Command Palette commands for the Signal module.
 * Provides quick access to the notifications inbox.
 *
 * @see \App\Services\Registry\CommandRegistry
 */
class SignalCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Signal module.
     *
     * @param  CommandRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'System', [
            [
                'label' => 'Notifications',
                'route' => 'notifications.index',
                'icon' => 'bell',
                'keywords' => ['notification', 'alerts', 'messages', 'inbox'],
                'order' => 50,
            ],
        ]);
    }
}
