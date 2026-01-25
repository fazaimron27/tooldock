<?php

/**
 * Signal Settings Registrar
 *
 * Registers module settings for the Signal notification module.
 * Provides settings for notification preferences that users can toggle.
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
 * Only registers settings for core/system notifications.
 * Other modules should register their own notification preferences.
 *
 * Settings registered:
 * - signal_notify_login: Toggle login notifications
 * - signal_notify_security: Toggle security alerts
 * - signal_notify_system: Toggle system notifications
 */
class SignalSettingsRegistrar
{
    /**
     * Register signal module settings.
     *
     * Registers boolean toggle settings for different notification
     * categories under the 'signal' group. All default to enabled.
     *
     * @param  SettingsRegistry  $registry  The application settings registry
     * @param  string  $moduleName  The module name for registration
     * @return void
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            group: 'signal',
            key: 'signal_notify_login',
            value: 'true',
            type: SettingType::Boolean,
            label: 'Login Notifications',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'signal',
            key: 'signal_notify_security',
            value: 'true',
            type: SettingType::Boolean,
            label: 'Security Alerts (password changes, lockouts)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'signal',
            key: 'signal_notify_system',
            value: 'true',
            type: SettingType::Boolean,
            label: 'System Notifications (welcome, role changes)',
            isSystem: false
        );
    }
}
