<?php

namespace App\Services\Registry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Register multiple settings at once.
     *
     * @param  string  $module  Module name
     * @param  string  $group  Setting group name
     * @param  array<int, array{key: string, value: mixed, type: SettingType, label: string, is_system?: bool}>  $settings  Array of setting definitions
     */
    public function registerMany(string $module, string $group, array $settings): void
    {
        foreach ($settings as $setting) {
            $this->register(
                module: $module,
                group: $group,
                key: $setting['key'],
                value: $setting['value'],
                type: $setting['type'],
                label: $setting['label'],
                isSystem: $setting['is_system'] ?? false
            );
        }
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
     * Get settings for a specific module.
     *
     * @param  string  $module  Module name
     * @return array<int, array{module: string, group: string, key: string, value: mixed, type: SettingType, label: string, is_system: bool}>
     */
    public function getSettingsByModule(string $module): array
    {
        $module = strtolower($module);

        return array_filter($this->settings, fn ($setting) => strtolower($setting['module']) === $module);
    }

    /**
     * Seed all registered settings into the database.
     *
     * This is automatically called by ModuleLifecycleService during module installation
     * and enabling. Only updates metadata (module, group, type, label, is_system) for existing settings
     * to preserve user-modified values. New settings are created with default values.
     *
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  bool  $strict  If true, any exception during seeding will cause the transaction to rollback.
     *                        If false (default), exceptions are logged but processing continues.
     */
    public function seed(bool $strict = false): void
    {
        if (empty($this->settings)) {
            return;
        }

        DB::transaction(function () use ($strict) {
            $registeredKeys = array_column($this->settings, 'key');
            $existingSettings = Setting::whereIn('key', $registeredKeys)
                ->get()
                ->keyBy('key');

            $created = 0;
            $updated = 0;
            $errors = 0;

            foreach ($this->settings as $setting) {
                try {
                    $existing = $existingSettings->get($setting['key']);

                    if ($existing) {
                        $typeChanged = $existing->type !== $setting['type'];
                        $updateData = [
                            'module' => $setting['module'],
                            'group' => $setting['group'],
                            'type' => $setting['type'],
                            'label' => $setting['label'],
                            'is_system' => $setting['is_system'],
                        ];

                        if ($typeChanged) {
                            $updateData['value'] = $setting['value'];
                            Log::info("SettingsRegistry: Type changed for setting '{$setting['key']}', resetting to default value", [
                                'module' => $setting['module'],
                                'old_type' => $existing->type->value,
                                'new_type' => $setting['type']->value,
                            ]);
                        }

                        $existing->update($updateData);
                        $updated++;
                    } else {
                        Setting::create([
                            'module' => $setting['module'],
                            'group' => $setting['group'],
                            'key' => $setting['key'],
                            'value' => $setting['value'],
                            'type' => $setting['type'],
                            'label' => $setting['label'],
                            'is_system' => $setting['is_system'],
                        ]);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('SettingsRegistry: Failed to seed setting', [
                        'module' => $setting['module'],
                        'key' => $setting['key'],
                        'error' => $e->getMessage(),
                    ]);

                    if ($strict) {
                        throw $e;
                    }
                }
            }

            if ($created > 0 || $updated > 0 || $errors > 0) {
                Log::debug('SettingsRegistry: Seeding completed', [
                    'created' => $created,
                    'updated' => $updated,
                    'errors' => $errors,
                    'total' => count($this->settings),
                ]);
            }
        });
    }

    /**
     * Clean up settings for a module when uninstalling.
     *
     * Removes all settings that belong to the specified module.
     * Wrapped in a database transaction to ensure atomicity.
     *
     * @param  string  $moduleName  The module name (e.g., 'Blog', 'Newsletter')
     * @return array{deleted: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        $moduleName = strtolower($moduleName);

        return DB::transaction(function () use ($moduleName) {
            $moduleSettings = $this->getSettingsByModule($moduleName);

            if (empty($moduleSettings)) {
                Log::info("SettingsRegistry: No settings found for module '{$moduleName}'");

                return [
                    'deleted' => 0,
                ];
            }

            $settingKeys = array_column($moduleSettings, 'key');
            $deleted = Setting::whereIn('key', $settingKeys)->delete();

            Log::info("SettingsRegistry: Cleaned up settings for module '{$moduleName}'", [
                'count' => $deleted,
                'keys' => $settingKeys,
            ]);

            return [
                'deleted' => $deleted,
            ];
        });
    }
}
