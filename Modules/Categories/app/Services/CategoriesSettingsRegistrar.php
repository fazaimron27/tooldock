<?php

namespace Modules\Categories\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;

/**
 * Handles settings registration for the Categories module.
 */
class CategoriesSettingsRegistrar
{
    /**
     * Register categories module settings.
     *
     * Group name should be lowercase module name for consistency.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            group: 'categories',
            key: 'categories_per_page',
            value: '20',
            type: SettingType::Integer,
            label: 'Categories Per Page',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'categories',
            key: 'categories_default_sort',
            value: 'created_at',
            type: SettingType::Text,
            label: 'Default Sort Column',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'categories',
            key: 'categories_default_sort_direction',
            value: 'desc',
            type: SettingType::Text,
            label: 'Default Sort Direction',
            isSystem: false
        );

        $registry->register(
            module: $moduleName,
            group: 'categories',
            key: 'categories_default_types',
            value: 'product,finance,project,inventory,expense,department',
            type: SettingType::Text,
            label: 'Default Category Types (comma-separated)',
            isSystem: false
        );
    }
}
