<?php

namespace App\Services\Core;

use App\Services\Cache\CacheService;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Collection;
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
        private SettingsRegistry $settingsRegistry,
        private CacheService $cacheService
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
     * Only updates if the value has actually changed to avoid unnecessary audit logs.
     * Automatically syncs config for settings that affect Laravel configuration.
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

        $currentValue = $setting->value;

        $normalizedCurrent = $this->normalizeValueForComparison($currentValue, $setting->type);
        $normalizedNew = $this->normalizeValueForComparison($value, $setting->type);

        if ($normalizedCurrent !== $normalizedNew) {
            $setting->update(['value' => $value]);
            $this->clearCache();
        }

        match ($key) {
            'app_name' => config(['app.name' => (string) $value]),
            'app_timezone' => config(['app.timezone' => (string) $value]),
            'app_locale' => config(['app.locale' => (string) $value]),
            'app_fallback_locale' => config(['app.fallback_locale' => (string) $value]),
            'mail_from_address' => config(['mail.from.address' => (string) $value]),
            'mail_from_name' => config(['mail.from.name' => (string) $value]),
            'session_lifetime' => config(['session.lifetime' => (int) $value]),
            'session_expire_on_close' => config(['session.expire_on_close' => filter_var($value, FILTER_VALIDATE_BOOLEAN)]),
            default => null,
        };
    }

    /**
     * Normalize a value for comparison to handle type differences.
     *
     * Converts values to a consistent format for comparison, handling cases where
     * the same logical value might be represented differently (e.g., true vs "1").
     *
     * @param  mixed  $value  The value to normalize
     * @param  \Modules\Settings\Enums\SettingType  $type  The setting type
     * @return mixed The normalized value
     */
    private function normalizeValueForComparison(mixed $value, \Modules\Settings\Enums\SettingType $type): mixed
    {
        return match ($type) {
            \Modules\Settings\Enums\SettingType::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            \Modules\Settings\Enums\SettingType::Integer => (int) $value,
            \Modules\Settings\Enums\SettingType::Text, \Modules\Settings\Enums\SettingType::Textarea => (string) $value,
        };
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
     * Loads from cache without auto-seeding - seeding should only happen explicitly.
     *
     * Note: Settings seeding should only happen explicitly via:
     * - SettingsService::sync() method
     * - ModuleLifecycleService during module installation
     * - Database seeders
     *
     * @return \Illuminate\Support\Collection<\Modules\Settings\Models\Setting>
     */
    private function loadAllSettings(): Collection
    {
        if ($this->shouldSkipCache()) {
            return $this->setting->all();
        }

        return $this->cacheService->rememberForever(
            self::CACHE_KEY,
            fn () => $this->setting->all(),
            self::CACHE_TAG
        );
    }

    /**
     * Check if we should skip using cache.
     *
     * Returns true during migrations or when running in console without database setup.
     * Checks full command line to catch optimize:clear and sub-commands.
     * Skips cache during migrations or optimize commands to prevent automatic seeding.
     */
    private function shouldSkipCache(): bool
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            $argv = $_SERVER['argv'] ?? [];
            $commandLine = implode(' ', array_slice($argv, 1));
            $command = $argv[1] ?? '';

            if (
                str_contains($command, 'migrate') ||
                str_contains($command, 'optimize') ||
                str_contains($commandLine, 'optimize:clear')
            ) {
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
        $this->cacheService->clearTag(self::CACHE_TAG, 'SettingsService');
    }
}
