<?php

namespace App\Services\Registry;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Models\Setting;

/**
 * Service for managing application settings with aggressive caching.
 *
 * Uses Cache::rememberForever to load all settings into memory,
 * ensuring zero database impact on normal requests. Cache is invalidated
 * immediately when settings are updated.
 */
class SettingsService
{
    private const CACHE_KEY = 'app_settings';

    public function __construct(
        private Setting $setting
    ) {}

    /**
     * Get a setting value by key.
     *
     * Does NOT query the database for a single key. Instead, uses
     * Cache::rememberForever to load ALL settings into a Collection,
     * then finds the specific key from that cached collection.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  Default value if key not found
     * @return mixed The setting value (automatically cast based on type)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $settings = $this->loadAllSettings();

            $setting = $settings->firstWhere('key', $key);

            if ($setting === null) {
                return $default;
            }

            return $setting->value;
        } catch (\Throwable $e) {
            // Return default if settings table doesn't exist (during migrations)
            return $default;
        }
    }

    /**
     * Set a setting value.
     *
     * Updates an existing setting's value in the database, then immediately forgets
     * the cache to force a reload on the next request.
     *
     * Note: This method only updates existing settings. New settings must be
     * registered via SettingsRegistry and will be created during sync.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $value  The value to set
     *
     * @throws \RuntimeException When the setting key doesn't exist in the database
     */
    public function set(string $key, mixed $value): void
    {
        $setting = $this->setting->where('key', $key)->first();

        if ($setting === null) {
            throw new \RuntimeException(
                "Setting key '{$key}' does not exist. Settings must be registered ".
                    'via SettingsRegistry before they can be updated.'
            );
        }

        $setting->update(['value' => $value]);

        Cache::forget(self::CACHE_KEY);

        // Immediately sync app.debug config if this is the app_debug setting
        if ($key === 'app_debug') {
            config(['app.debug' => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * Get all settings grouped by their 'group' column.
     *
     * Returns a collection grouped by the 'group' column for UI display.
     * Format: ['general' => [...], 'finance' => [...]]
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>
     */
    public function all(): Collection
    {
        $settings = $this->loadAllSettings();

        return $settings->groupBy('group');
    }

    /**
     * Force sync all registered settings to database.
     *
     * Clears cache and syncs all registered settings from SettingsRegistry.
     * Useful when modules are installed/enabled to ensure their settings are added.
     *
     * Note: This only updates metadata (module, group, type, label, is_system) for
     * existing settings to preserve user-modified values. New settings are created
     * with their default values.
     *
     * @return void
     */
    public function sync(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->syncRegisteredSettings();
    }

    /**
     * Clean up settings for a module when uninstalling.
     *
     * Removes all settings that belong to the specified module.
     * Clears the cache after deletion to ensure changes are reflected immediately.
     *
     * @param  string  $moduleName  The module name (e.g., 'Blog', 'Newsletter')
     */
    public function cleanup(string $moduleName): void
    {
        $settingKeys = $this->setting->where('module', $moduleName)
            ->pluck('key')
            ->toArray();

        if (empty($settingKeys)) {
            Log::info("SettingsService: No settings found for module '{$moduleName}'");

            return;
        }

        $deleted = $this->setting->where('module', $moduleName)->delete();

        Log::info("SettingsService: Cleaned up settings for module '{$moduleName}'", [
            'count' => $deleted,
            'keys' => $settingKeys,
        ]);

        if ($deleted > 0) {
            Cache::forget(self::CACHE_KEY);
        }
    }

    /**
     * Load all settings from cache or database.
     *
     * Uses Cache::rememberForever to cache all settings.
     * Cache is invalidated when settings are updated via set().
     * Automatically syncs any missing registered settings from SettingsRegistry
     * only when cache is empty (first load or after cache clear).
     *
     * @return \Illuminate\Support\Collection<\Modules\Settings\Models\Setting>
     */
    private function loadAllSettings(): Collection
    {
        // Skip cache during migrations or when cache table doesn't exist
        if ($this->shouldSkipCache()) {
            return $this->setting->all();
        }

        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                $this->syncRegisteredSettings();

                return $this->setting->all();
            });
        } catch (\Throwable $e) {
            // Fallback to direct database query if cache fails
            // This can happen during migrations when cache table doesn't exist
            return $this->setting->all();
        }
    }

    /**
     * Check if we should skip using cache.
     *
     * Returns true during migrations or when running in console without database setup.
     */
    private function shouldSkipCache(): bool
    {
        // Skip cache during migrations
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            $argv = $_SERVER['argv'] ?? [];
            $command = $argv[1] ?? '';
            if (str_contains($command, 'migrate')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sync registered settings from SettingsRegistry to database.
     *
     * Ensures all settings registered by modules are present in the database.
     * Only updates metadata (module, group, type, label, is_system) for existing settings
     * to preserve user-modified values. New settings are created with default values.
     * Only called when cache is empty (cache miss scenario).
     *
     * @return void
     */
    private function syncRegisteredSettings(): void
    {
        $registry = app(SettingsRegistry::class);
        $registeredSettings = $registry->getSettings();

        if (empty($registeredSettings)) {
            return;
        }

        $registeredKeys = array_column($registeredSettings, 'key');
        $existingSettings = $this->setting->whereIn('key', $registeredKeys)
            ->get()
            ->keyBy('key');

        foreach ($registeredSettings as $setting) {
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
                    Log::info("SettingsService: Type changed for setting '{$setting['key']}', resetting to default value", [
                        'old_type' => $existing->type->value,
                        'new_type' => $setting['type']->value,
                    ]);
                }

                $existing->update($updateData);
            } else {
                $this->setting->create([
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
