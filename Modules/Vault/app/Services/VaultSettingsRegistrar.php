<?php

namespace Modules\Vault\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles settings registration for the Vault module.
 */
class VaultSettingsRegistrar
{
    /**
     * Register vault module settings.
     *
     * Group name should be lowercase module name for consistency.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            group: 'vault',
            key: 'vault_per_page',
            value: '20',
            type: SettingType::Integer,
            label: 'Vault Items Per Page',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'vault',
            key: 'vault_totp_code_length',
            value: '6',
            type: SettingType::Integer,
            label: 'TOTP Code Length',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'vault',
            key: 'vault_totp_period',
            value: '30',
            type: SettingType::Integer,
            label: 'TOTP Period (seconds)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'vault',
            key: 'vault_lock_enabled',
            value: 'false',
            type: SettingType::Boolean,
            label: 'Enable Vault Lock',
            isSystem: false
        );
    }
}
