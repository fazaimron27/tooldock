<?php

namespace App\Services\Registry;

use Illuminate\Support\Facades\Cache;
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
     * Register a single setting for a module.
     *
     * Validates that the setting key is unique across all modules. If a duplicate
     * key is detected from a different module, a RuntimeException is thrown.
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
     * Register multiple settings for a module in a single call.
     *
     * Convenience method that iterates through an array of setting definitions
     * and registers each one using the register() method.
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
     * Sync all registered settings to the database.
     *
     * Creates new settings with default values and updates metadata for existing
     * settings (module, group, type, label, is_system) while preserving user-modified
     * values. If a setting's type changes, the value is reset to the default.
     *
     * Automatically called by ModuleLifecycleService during module installation
     * and enabling. Clears the settings cache after successful seeding to ensure
     * changes are immediately visible.
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

                Cache::forget('app_settings');
            }
        });
    }

    /**
     * Remove all settings for a module during uninstallation.
     *
     * Queries the database directly by module name (case-insensitive) rather than
     * relying on the registry, since uninstalled modules are no longer registered
     * and their service providers don't boot. Clears the settings cache after
     * successful deletion to ensure changes are immediately visible in the UI.
     *
     * @param  string  $moduleName  The module name (e.g., 'Blog', 'Newsletter')
     * @return array{deleted: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        return DB::transaction(function () use ($moduleName) {
            $deleted = Setting::whereRaw('LOWER(module) = ?', [strtolower($moduleName)])->delete();

            if ($deleted > 0) {
                Log::info("SettingsRegistry: Cleaned up settings for module '{$moduleName}'", [
                    'count' => $deleted,
                ]);

                Cache::forget('app_settings');
            } else {
                Log::info("SettingsRegistry: No settings found for module '{$moduleName}'");
            }

            return [
                'deleted' => $deleted,
            ];
        });
    }
}
