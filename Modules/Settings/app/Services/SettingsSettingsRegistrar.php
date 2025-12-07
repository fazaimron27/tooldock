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
            group: 'general',
            key: 'app_timezone',
            value: config('app.timezone', 'UTC'),
            type: SettingType::Text,
            label: 'Application Timezone',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'general',
            key: 'app_locale',
            value: config('app.locale', 'en'),
            type: SettingType::Text,
            label: 'Application Locale',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'general',
            key: 'app_fallback_locale',
            value: config('app.fallback_locale', 'en'),
            type: SettingType::Text,
            label: 'Fallback Locale',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'mail',
            key: 'mail_from_address',
            value: config('mail.from.address', 'hello@example.com'),
            type: SettingType::Text,
            label: 'Mail From Address',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'mail',
            key: 'mail_from_name',
            value: config('mail.from.name', 'Example'),
            type: SettingType::Text,
            label: 'Mail From Name',
            isSystem: false
        );
    }
}
