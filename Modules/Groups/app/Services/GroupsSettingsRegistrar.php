<?php

namespace Modules\Groups\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles settings registration for the Groups module.
 */
class GroupsSettingsRegistrar
{
    /**
     * Register groups module settings.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'groups', [
            'display' => [
                'label' => 'Display Settings',
                'description' => 'Configure how groups are displayed',
                'settings' => [
                    ['key' => 'groups_per_page', 'value' => '20', 'type' => SettingType::Integer, 'label' => 'Groups Per Page'],
                    ['key' => 'groups_default_sort', 'value' => 'created_at', 'type' => SettingType::Text, 'label' => 'Groups Default Sort Column'],
                    ['key' => 'groups_default_sort_direction', 'value' => 'desc', 'type' => SettingType::Text, 'label' => 'Groups Default Sort Direction'],
                ],
            ],
            'members' => [
                'label' => 'Member Settings',
                'description' => 'Configure member display and pagination',
                'settings' => [
                    ['key' => 'groups_members_per_page', 'value' => '10', 'type' => SettingType::Integer, 'label' => 'Group Members Per Page'],
                    ['key' => 'groups_members_default_sort', 'value' => 'name', 'type' => SettingType::Text, 'label' => 'Group Members Default Sort Column'],
                    ['key' => 'groups_members_default_sort_direction', 'value' => 'asc', 'type' => SettingType::Text, 'label' => 'Group Members Default Sort Direction'],
                    ['key' => 'groups_available_users_per_page', 'value' => '20', 'type' => SettingType::Integer, 'label' => 'Available Users Per Page'],
                ],
            ],
            'performance' => [
                'label' => 'Performance',
                'description' => 'System performance settings',
                'settings' => [
                    ['key' => 'groups_large_group_threshold', 'value' => '100', 'type' => SettingType::Integer, 'label' => 'Large Group Threshold', 'is_system' => true],
                    ['key' => 'groups_member_data_chunk_size', 'value' => '1000', 'type' => SettingType::Integer, 'label' => 'Member Data Chunk Size', 'is_system' => true],
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Configure notification preferences',
                'permission' => 'groups.preferences.view',
                'settings' => [
                    ['key' => 'groups_notify_enabled', 'value' => 'true', 'type' => SettingType::Boolean, 'label' => 'Group Notifications (membership changes)', 'scope' => 'user'],
                ],
            ],
        ]);
    }
}
