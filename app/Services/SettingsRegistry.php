<?php

namespace App\Services;

use Modules\Settings\Enums\SettingType;
use Modules\Settings\Models\Setting;

/**
 * Registry for managing application settings registration.
 *
 * Allows modules to register their settings during service provider boot,
 * which are then automatically seeded into the database.
 *
 * Group name convention: Use lowercase module name (e.g., 'blog' for Blog module).
 * This ensures proper cleanup when modules are uninstalled.
 */
class SettingsRegistry
{
    /**
     * @var array<int, array{module: string, group: string, key: string, value: mixed, type: SettingType, label: string, is_system: bool}>
     */
    private array $settings = [];

    /**
     * Track registered keys to prevent duplicates.
     *
     * @var array<string, string> Key => Module name
     */
    private array $registeredKeys = [];

    /**
     * Register a setting.
     *
     * @param  string  $module  Module name (e.g., 'Blog', 'Newsletter')
     * @param  string  $group  Setting group name (should be lowercase module name, e.g., 'blog')
     * @param  string  $key  Unique setting key
     * @param  mixed  $value  Default value
     * @param  SettingType  $type  Setting type (Text, Boolean, Integer, Textarea)
     * @param  string  $label  Human-readable label for the UI
     * @param  bool  $isSystem  Whether this is a system setting (not editable via UI)
     *
     * @throws \RuntimeException When a duplicate key is registered by a different module
     */
    public function register(
        string $module,
        string $group,
        string $key,
        mixed $value,
        SettingType $type,
        string $label,
        bool $isSystem = false
    ): void {
        // Validate for duplicate keys
        if (isset($this->registeredKeys[$key]) && $this->registeredKeys[$key] !== $module) {
            throw new \RuntimeException(
                "Setting key '{$key}' is already registered by module '{$this->registeredKeys[$key]}'. ".
                    "Module '{$module}' cannot register a duplicate key."
            );
        }

        $this->registeredKeys[$key] = $module;

        $this->settings[] = [
            'module' => $module,
            'group' => $group,
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'label' => $label,
            'is_system' => $isSystem,
        ];
    }

    /**
     * Get all registered settings.
     *
     * @return array<int, array{module: string, group: string, key: string, value: mixed, type: SettingType, label: string, is_system: bool}>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Seed all registered settings into the database.
     *
     * This should be called from a seeder after all modules have registered their settings.
     * Only updates metadata (module, group, type, label, is_system) for existing settings
     * to preserve user-modified values. New settings are created with default values.
     */
    public function seed(): void
    {
        if (empty($this->settings)) {
            return;
        }

        // Get all existing settings in one query for efficiency
        $registeredKeys = array_column($this->settings, 'key');
        $existingSettings = Setting::whereIn('key', $registeredKeys)
            ->get()
            ->keyBy('key');

        foreach ($this->settings as $setting) {
            $existing = $existingSettings->get($setting['key']);

            if ($existing) {
                // Setting exists: validate type change and update metadata
                // If type changed, reset value to default to prevent incompatibility
                $typeChanged = $existing->type !== $setting['type'];
                $updateData = [
                    'module' => $setting['module'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                    'is_system' => $setting['is_system'],
                ];

                // If type changed, reset value to default to prevent incompatibility
                if ($typeChanged) {
                    $updateData['value'] = $setting['value'];
                }

                $existing->update($updateData);
            } else {
                // Setting doesn't exist: create with default value
                Setting::create([
                    'module' => $setting['module'],
                    'group' => $setting['group'],
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                    'is_system' => $setting['is_system'],
                ]);
            }
        }
    }
}
