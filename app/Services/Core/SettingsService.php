<?php

namespace App\Services\Core;

use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Models\Setting;

/**
 * Service for managing application settings with aggressive caching.
 *
 * Optimized for Redis with cache tags for efficient invalidation.
 * Uses Cache::rememberForever to load all settings into memory,
 * ensuring zero database impact on normal requests. Cache is invalidated
 * immediately when settings are updated via tag-based flush.
 *
 * Note: For registration and seeding, use SettingsRegistry.
 * This service handles runtime operations (get, set, all) with caching.
 */
class SettingsService
{
    private const CACHE_KEY = 'app_settings';

    private const CACHE_TAG = 'settings';

    public function __construct(
        private Setting $setting,
        private SettingsRegistry $settingsRegistry
    ) {}

    /**
     * Get a setting value by key.
     *
     * Uses Cache::rememberForever to load ALL settings into a Collection,
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
            return $default;
        }
    }

    /**
     * Set a setting value.
     *
     * Updates an existing setting's value in the database, then immediately clears
     * the cache via Redis tags to force a reload on the next request.
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

        $this->clearCache();

        if ($key === 'app_debug') {
            config(['app.debug' => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * Get all settings grouped by their 'group' column.
     *
     * Returns a collection grouped by the 'group' column for UI display.
     * Groups are sorted alphabetically, and settings within each group
     * are sorted by label alphabetically.
     * Format: ['general' => [...], 'finance' => [...]]
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>
     */
    public function all(): Collection
    {
        $settings = $this->loadAllSettings();

        return $settings
            ->groupBy('group')
            ->sortKeys()
            ->map(fn (Collection $groupSettings) => $groupSettings->sortBy('label')->values());
    }

    /**
     * Force sync all registered settings to database.
     *
     * Delegates to SettingsRegistry::seed() to sync all registered settings.
     * Clears cache after syncing to ensure changes are reflected immediately.
     *
     * @return void
     */
    public function sync(): void
    {
        $this->settingsRegistry->seed();
        $this->clearCache();
    }

    /**
     * Clean up settings for a module when uninstalling.
     *
     * Delegates to SettingsRegistry::cleanup() to remove settings for a module.
     * Clears the cache after cleanup to ensure changes are reflected immediately.
     *
     * @param  string  $moduleName  The module name (e.g., 'Blog', 'Newsletter')
     * @return array{deleted: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        $result = $this->settingsRegistry->cleanup($moduleName);

        if ($result['deleted'] > 0) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Load all settings from cache or database.
     *
     * Optimized for Redis with cache tags for efficient invalidation.
     * Uses Cache::rememberForever to cache all settings.
     * Cache is invalidated when settings are updated via set().
     * Automatically syncs any missing registered settings from SettingsRegistry
     * only when cache is empty (first load or after cache clear).
     *
     * @return \Illuminate\Support\Collection<\Modules\Settings\Models\Setting>
     */
    private function loadAllSettings(): Collection
    {
        if ($this->shouldSkipCache()) {
            return $this->setting->all();
        }

        try {
            return Cache::tags([self::CACHE_TAG])->rememberForever(self::CACHE_KEY, function () {
                $this->settingsRegistry->seed();

                return $this->setting->all();
            });
        } catch (\Throwable $e) {
            Log::warning('SettingsService: Cache error, falling back to database', [
                'cache_key' => self::CACHE_KEY,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
     * Clear all settings caches.
     *
     * Optimized for Redis - uses tag-based flush for efficient invalidation.
     * This method is called automatically when settings are updated, synced, or cleaned up,
     * ensuring cache consistency without manual intervention.
     */
    private function clearCache(): void
    {
        try {
            Cache::tags([self::CACHE_TAG])->flush();

            Log::debug('SettingsService: Settings cache cleared via Redis tags', [
                'tag' => self::CACHE_TAG,
            ]);
        } catch (\Throwable $e) {
            Log::warning('SettingsService: Failed to clear cache', [
                'tag' => self::CACHE_TAG,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
