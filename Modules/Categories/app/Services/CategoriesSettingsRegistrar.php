<?php

/**
 * Categories Settings Registrar.
 *
 * Registers configurable settings for the Categories module including
 * display preferences and available category types.
 *
 * @author Tool Dock Team
 * @license MIT
 */

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
     * @param  SettingsRegistry  $registry  The settings registry
     * @param  string  $moduleName  The module name
     * @return void
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'categories', [
            'display' => [
                'label' => 'Display Settings',
                'description' => 'Configure how categories are displayed',
                'settings' => [
                    ['key' => 'categories_per_page', 'value' => '20', 'type' => SettingType::Integer, 'label' => 'Categories Per Page'],
                    ['key' => 'categories_default_sort', 'value' => 'created_at', 'type' => SettingType::Text, 'label' => 'Default Sort Column'],
                    ['key' => 'categories_default_sort_direction', 'value' => 'desc', 'type' => SettingType::Text, 'label' => 'Default Sort Direction'],
                ],
            ],
            'types' => [
                'label' => 'Category Types',
                'description' => 'Configure available category types',
                'settings' => [
                    ['key' => 'categories_default_types', 'value' => 'product,finance,project,inventory,expense,department', 'type' => SettingType::Text, 'label' => 'Default Category Types (comma-separated)'],
                ],
            ],
        ]);
    }
}
