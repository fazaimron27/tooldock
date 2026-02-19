<?php

/**
 * Settings Dashboard Service.
 *
 * Handles dashboard widget registration and data retrieval
 * for the Settings module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Settings\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Settings\Enums\SettingType;
use Modules\Settings\Models\Setting;

/**
 * Handles dashboard widget registration and data retrieval for the Settings module.
 */
class SettingsDashboardService
{
    /**
     * Register all dashboard widgets for the Settings module.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'System Configuration',
            'Manage system-wide settings and preferences.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Settings',
                value: fn () => Setting::count(),
                icon: 'Settings',
                module: $moduleName,
                order: 70,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'table',
                title: 'Settings Configuration',
                value: 0,
                icon: 'Settings',
                module: $moduleName,
                description: 'Manage and view all application settings',
                data: fn () => $this->getSettingsTableData(),
                config: [
                    'columns' => $this->getSettingsTableColumns(),
                ],
                order: 71,
                scope: 'detail'
            )
        );
    }

    /**
     * Formats settings data for the table widget.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSettingsTableData(): array
    {
        return Setting::query()
            ->orderBy('module')
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->map(function (Setting $setting) {
                return [
                    'id' => $setting->id,
                    'module' => $setting->module ?? 'N/A',
                    'group' => $setting->group ?? 'N/A',
                    'key' => $setting->key,
                    'label' => $setting->label ?? $setting->key,
                    'value' => $this->formatSettingValue($setting),
                    'type' => ucfirst($setting->type->value),
                    'is_system' => $setting->is_system ? 'Yes' : 'No',
                ];
            })
            ->toArray();
    }

    /**
     * Formats a setting value for display in the table.
     *
     * @param  Setting  $setting
     * @return string
     */
    private function formatSettingValue(Setting $setting): string
    {
        $value = $setting->value;

        if ($value === null) {
            return 'N/A';
        }

        return match ($setting->type) {
            SettingType::Boolean => $value ? 'Yes' : 'No',
            SettingType::Integer => (string) $value,
            SettingType::Textarea => strlen($value) > 50 ? substr($value, 0, 50).'...' : $value,
            default => (string) $value,
        };
    }

    /**
     * Returns column definitions for the settings table widget.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSettingsTableColumns(): array
    {
        return [
            [
                'accessorKey' => 'module',
                'header' => 'Module',
            ],
            [
                'accessorKey' => 'group',
                'header' => 'Group',
            ],
            [
                'accessorKey' => 'key',
                'header' => 'Key',
            ],
            [
                'accessorKey' => 'label',
                'header' => 'Label',
            ],
            [
                'accessorKey' => 'value',
                'header' => 'Value',
            ],
            [
                'accessorKey' => 'type',
                'header' => 'Type',
            ],
            [
                'accessorKey' => 'is_system',
                'header' => 'System',
            ],
        ];
    }
}
