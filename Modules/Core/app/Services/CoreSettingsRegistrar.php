<?php

/**
 * Core Settings Registrar
 *
 * Registers module settings for the Core module including
 * user interface preferences like theme and notification sounds.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles settings registration for the Core module.
 */
class CoreSettingsRegistrar
{
    /**
     * Register core module settings.
     *
     * @param  SettingsRegistry  $registry  The settings registry service
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'core', [
            'ui_preferences' => [
                'label' => 'User Interface Preferences',
                'description' => 'Customize your user interface settings',
                'settings' => [
                    [
                        'key' => 'core_theme',
                        'value' => 'system',
                        'type' => SettingType::Select,
                        'label' => 'Application Theme',
                        'options' => ['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'],
                        'scope' => 'user',
                    ],
                ],
            ],
            'notification_preferences' => [
                'label' => 'Notification Sound Preferences',
                'description' => 'Configure notification sound and desktop alerts',
                'settings' => [
                    [
                        'key' => 'core_notification_sound',
                        'value' => 'true',
                        'type' => SettingType::Boolean,
                        'label' => 'Enable Notification Sound',
                        'scope' => 'user',
                    ],
                    [
                        'key' => 'core_notification_volume',
                        'value' => '50',
                        'type' => SettingType::Percentage,
                        'label' => 'Notification Volume',
                        'scope' => 'user',
                    ],
                    [
                        'key' => 'core_notification_desktop',
                        'value' => 'false',
                        'type' => SettingType::Boolean,
                        'label' => 'Enable Desktop Notifications',
                        'scope' => 'user',
                    ],
                ],
            ],
        ]);
    }
}
