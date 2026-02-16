<?php

/**
 * Vault Settings Registrar
 *
 * Registers configurable settings for the Vault module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Class VaultSettingsRegistrar
 *
 * Registers display, TOTP, security (lock), and notification
 * settings for administrative and per-user configuration.
 *
 * @see \App\Services\Registry\SettingsRegistry
 */
class VaultSettingsRegistrar
{
    /**
     * Register vault module settings.
     *
     * @param  SettingsRegistry  $registry  The central settings registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'vault', [
            'display' => [
                'label' => 'Display Settings',
                'description' => 'Configure vault item display',
                'settings' => [
                    ['key' => 'vault_per_page', 'value' => '20', 'type' => SettingType::Integer, 'label' => 'Vault Items Per Page'],
                ],
            ],
            'totp' => [
                'label' => 'TOTP Settings',
                'description' => 'Configure two-factor authentication codes',
                'settings' => [
                    ['key' => 'vault_totp_code_length', 'value' => '6', 'type' => SettingType::Integer, 'label' => 'TOTP Code Length'],
                    ['key' => 'vault_totp_period', 'value' => '30', 'type' => SettingType::Integer, 'label' => 'TOTP Period (seconds)'],
                ],
            ],
            'security' => [
                'label' => 'Security',
                'description' => 'Configure vault lock and security features',
                'permission' => 'vaults.preferences.view',
                'settings' => [
                    ['key' => 'vault_lock_enabled', 'value' => 'false', 'type' => SettingType::Boolean, 'label' => 'Enable Vault Lock', 'scope' => 'user'],
                    ['key' => 'vault_lock_timeout', 'value' => '15', 'type' => SettingType::Integer, 'label' => 'Vault Lock Timeout (minutes)', 'scope' => 'user'],
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Configure notification preferences',
                'permission' => 'vaults.preferences.view',
                'settings' => [
                    ['key' => 'vault_notify_enabled', 'value' => 'true', 'type' => SettingType::Boolean, 'label' => 'Vault Notifications (lock/unlock, PIN changes)', 'scope' => 'user'],
                ],
            ],
        ]);
    }
}
