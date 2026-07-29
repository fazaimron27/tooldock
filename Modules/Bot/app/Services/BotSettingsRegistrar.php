<?php

/**
 * Bot Settings Registrar
 *
 * Registers user-scoped notification preference toggles for the Bot module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

class BotSettingsRegistrar
{
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'bot', [
            'notifications' => [
                'label' => 'Bot Notification Preferences',
                'description' => 'Choose which bot events trigger real-time notifications',
                'permission' => 'bot.bridge.view',
                'settings' => [
                    [
                        'key' => 'bot_notify_enabled',
                        'value' => 'true',
                        'type' => SettingType::Boolean,
                        'label' => 'Bot message delivery notifications',
                        'scope' => 'user',
                    ],
                ],
            ],
        ]);
    }
}
