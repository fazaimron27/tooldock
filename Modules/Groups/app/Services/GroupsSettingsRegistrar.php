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
     *
     * Group name should be lowercase module name for consistency.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        // Pagination settings for groups listing
        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_per_page',
            value: '20',
            type: SettingType::Integer,
            label: 'Groups Per Page',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_default_sort',
            value: 'created_at',
            type: SettingType::Text,
            label: 'Groups Default Sort Column',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_default_sort_direction',
            value: 'desc',
            type: SettingType::Text,
            label: 'Groups Default Sort Direction',
            isSystem: false
        );

        // Pagination settings for members listing
        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_members_per_page',
            value: '10',
            type: SettingType::Integer,
            label: 'Group Members Per Page',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_members_default_sort',
            value: 'name',
            type: SettingType::Text,
            label: 'Group Members Default Sort Column',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_members_default_sort_direction',
            value: 'asc',
            type: SettingType::Text,
            label: 'Group Members Default Sort Direction',
            isSystem: false
        );

        // Pagination settings for available users dialog
        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_available_users_per_page',
            value: '20',
            type: SettingType::Integer,
            label: 'Available Users Per Page',
            isSystem: false
        );

        // Performance settings (system/internal)
        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_large_group_threshold',
            value: '100',
            type: SettingType::Integer,
            label: 'Large Group Threshold',
            isSystem: true
        );

        $registry->register(
            module: $moduleName,
            group: 'groups',
            key: 'groups_member_data_chunk_size',
            value: '1000',
            type: SettingType::Integer,
            label: 'Member Data Chunk Size',
            isSystem: true
        );
    }
}
