<?php

namespace Modules\Settings\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles default application settings registration for the Settings module.
 */
class SettingsSettingsRegistrar
{
    /**
     * Register default application settings.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            group: 'general',
            key: 'app_name',
            value: 'Tool Dock',
            type: SettingType::Text,
            label: 'Application Name',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'general',
            key: 'app_logo',
            value: 'Ship',
            type: SettingType::Text,
            label: 'Application Logo Icon (Lucide)',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'general',
            key: 'date_format',
            value: 'd/m/Y',
            type: SettingType::Text,
            label: 'Date Format',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'finance',
            key: 'currency_symbol',
            value: 'Rp',
            type: SettingType::Text,
            label: 'Currency Symbol',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'system',
            key: 'app_debug',
            value: '0',
            type: SettingType::Boolean,
            label: 'Application Debug Mode',
            isSystem: true
        );
    }
}
