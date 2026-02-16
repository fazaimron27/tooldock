<?php

/**
 * Signal Settings Registrar
 *
 * Registers module settings for the Signal notification module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Class SignalSettingsRegistrar
 *
 * Handles settings registration for the Signal module.
 * Registers notification preference toggles that support per-user scoping.
 *
 * @see \App\Services\Registry\SettingsRegistry
 */
class SignalSettingsRegistrar
{
    /**
     * Register signal module settings.
     *
     * @param  SettingsRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'signal', [
            'notifications' => [
                'label' => 'Notification Preferences',
                'description' => 'Choose which notifications you want to receive',
                'permission' => 'signals.preferences.view',
                'settings' => [
                    ['key' => 'signal_notify_login', 'value' => 'true', 'type' => SettingType::Boolean, 'label' => 'Login Notifications', 'scope' => 'user'],
                    ['key' => 'signal_notify_security', 'value' => 'true', 'type' => SettingType::Boolean, 'label' => 'Security Alerts (password changes, lockouts)', 'scope' => 'user'],
                    ['key' => 'signal_notify_system', 'value' => 'true', 'type' => SettingType::Boolean, 'label' => 'System Notifications (welcome, role changes)', 'scope' => 'user'],
                ],
            ],
        ]);
    }
}
