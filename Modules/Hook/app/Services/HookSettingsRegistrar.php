<?php

/**
 * Hook Settings Registrar
 *
 * Registers user-scoped notification preference toggles for the Hook module.
 * These settings appear in the user's notification preferences and are used
 * by the Signal system to gate hook-related flash notifications.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Class HookSettingsRegistrar
 *
 * Registers user-facing toggles for hook notification preferences.
 *
 * @see SettingsRegistry
 */
class HookSettingsRegistrar
{
    /**
     * Register hook notification settings.
     *
     * @param  SettingsRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'hook', [
            'notifications' => [
                'label' => 'Webhook Notification Preferences',
                'description' => 'Choose which webhook events trigger real-time notifications',
                'permission' => 'hook.inbound.view',
                'settings' => [
                    [
                        'key' => 'hook_inbound_notify_enabled',
                        'value' => 'true',
                        'type' => SettingType::Boolean,
                        'label' => 'Inbound webhook received notifications',
                        'scope' => 'user',
                    ],
                    [
                        'key' => 'hook_outbound_notify_enabled',
                        'value' => 'true',
                        'type' => SettingType::Boolean,
                        'label' => 'Outbound webhook delivery notifications',
                        'scope' => 'user',
                    ],
                ],
            ],
        ]);
    }
}
