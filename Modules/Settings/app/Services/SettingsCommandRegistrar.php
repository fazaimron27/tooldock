<?php

/**
 * Settings Command Registrar.
 *
 * Registers Command Palette commands for the Settings module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Settings\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Settings module.
 */
class SettingsCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Settings module.
     *
     * @param  CommandRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'System', [
            [
                'label' => 'Settings',
                'route' => 'settings.index',
                'icon' => 'settings',
                'permission' => 'settings.config.view',
                'keywords' => ['settings', 'preferences', 'configuration', 'options'],
                'order' => 70,
            ],
        ]);
    }
}
