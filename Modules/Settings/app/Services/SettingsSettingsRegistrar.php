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
        $registry->registerMany($moduleName, 'general', [
            'application' => [
                'label' => 'Application',
                'description' => 'Core application settings',
                'settings' => [
                    ['key' => 'app_name', 'value' => 'Tool Dock', 'type' => SettingType::Text, 'label' => 'Application Name'],
                    ['key' => 'app_logo', 'value' => 'Ship', 'type' => SettingType::Text, 'label' => 'Application Logo Icon (Lucide)'],
                ],
            ],
            'regional' => [
                'label' => 'Regional Settings',
                'description' => 'Configure date, time, and locale preferences',
                'settings' => [
                    ['key' => 'date_format', 'value' => 'd/m/Y', 'type' => SettingType::Text, 'label' => 'Date Format'],
                    ['key' => 'app_timezone', 'value' => config('app.timezone', 'UTC'), 'type' => SettingType::Text, 'label' => 'Application Timezone'],
                    ['key' => 'app_locale', 'value' => config('app.locale', 'en'), 'type' => SettingType::Text, 'label' => 'Application Locale'],
                    ['key' => 'app_fallback_locale', 'value' => config('app.fallback_locale', 'en'), 'type' => SettingType::Text, 'label' => 'Fallback Locale'],
                ],
            ],
        ]);

        $registry->registerMany($moduleName, 'mail', [
            'mail_config' => [
                'label' => 'Mail Configuration',
                'description' => 'Configure email sender settings',
                'settings' => [
                    ['key' => 'mail_from_address', 'value' => config('mail.from.address', 'hello@example.com'), 'type' => SettingType::Text, 'label' => 'Mail From Address'],
                    ['key' => 'mail_from_name', 'value' => config('mail.from.name', 'Example'), 'type' => SettingType::Text, 'label' => 'Mail From Name'],
                ],
            ],
        ]);
    }
}
