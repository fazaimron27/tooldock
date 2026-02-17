<?php

/**
 * Routine Settings Registrar
 *
 * Registers configurable settings for the Routine module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Class RoutineSettingsRegistrar
 *
 * Registers display, habit defaults, and notification settings
 * for the Routine module's administrative and per-user configuration.
 *
 * @see \App\Services\Registry\SettingsRegistry
 */
class RoutineSettingsRegistrar
{
    /**
     * Register routine module settings.
     *
     * @param  SettingsRegistry  $registry  The central settings registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'routine', [
            'display' => [
                'label' => 'Display Settings',
                'description' => 'Configure habit tracker display preferences',
                'settings' => [
                    ['key' => 'routine_week_start', 'value' => 'monday', 'type' => SettingType::Text, 'label' => 'Week Start Day'],
                ],
            ],
            'defaults' => [
                'label' => 'Habit Defaults',
                'description' => 'Configure default values for new habits',
                'settings' => [
                    ['key' => 'routine_default_goal_per_week', 'value' => '7', 'type' => SettingType::Integer, 'label' => 'Default Goal Per Week'],
                    ['key' => 'routine_default_habit_type', 'value' => 'boolean', 'type' => SettingType::Text, 'label' => 'Default Habit Type (boolean/measurable)'],
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Configure routine notification preferences',
                'permission' => 'routines.preferences.view',
                'settings' => [
                    ['key' => 'routine_notify_enabled', 'value' => 'true', 'type' => SettingType::Boolean, 'label' => 'Routine Notifications', 'scope' => 'user'],
                    ['key' => 'routine_streak_milestone_notify', 'value' => 'true', 'type' => SettingType::Boolean, 'label' => 'Streak Milestone Notifications', 'scope' => 'user'],
                ],
            ],
        ]);
    }
}
