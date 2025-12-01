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
                    }
                } catch (\Exception $e) {
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
        });
    }
}
