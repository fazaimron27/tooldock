<?php

namespace Modules\Signal\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Signal module.
 */
class SignalCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Signal module.
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
