<?php

/**
 * User Preference Service
 *
 * Manages per-user preferences with fallback to global settings.
 * Users can override settings marked with scope='user' in the
 * SettingsRegistry, with automatic type casting and caching.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Core;

use App\Services\Cache\CacheService;
use App\Services\Registry\SettingsRegistry;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Models\User;
use Modules\Core\Models\UserPreference;
use Modules\Settings\Enums\SettingType;

/**
 * Class UserPreferenceService
 *
 * Provides a layered settings system where user-specific overrides
 * take precedence over global defaults. Preferences are cached per
 * user with tag-based invalidation. When a preference matches the
 * global default, it is automatically deleted to keep storage clean.
 *
 * @see \App\Services\Core\SettingsService For global settings fallback
 * @see \App\Services\Registry\SettingsRegistry For scope definitions
 * @see \Modules\Core\Models\UserPreference For the persistence model
 */
class UserPreferenceService
{
    private const CACHE_KEY_PREFIX = 'user_preferences:';

    private const CACHE_TAG = 'user_preferences';

    /**
     * Create a new UserPreferenceService instance.
     *
     * @param  \App\Services\Core\SettingsService  $settingsService  Global settings provider for fallback values
     * @param  \App\Services\Registry\SettingsRegistry  $settingsRegistry  Registry for scope and type metadata
     * @param  \App\Services\Cache\CacheService  $cacheService  Cache service for tag-based caching
     */
    public function __construct(
        private SettingsService $settingsService,
        private SettingsRegistry $settingsRegistry,
        private CacheService $cacheService
    ) {}

    /**
     * Get a user's preference value with global fallback.
     *
     * Returns the user's override if one exists, otherwise falls
     * back to the global setting value. Values are automatically
     * cast to the appropriate type based on the setting definition.
     *
     * @param  \Modules\Core\Models\User  $user  The user to retrieve the preference for
     * @param  string  $key  The setting key (e.g., 'app_timezone', 'notifications_enabled')
     * @param  mixed  $default  Default value if neither preference nor global setting exists
     * @return mixed The preference value, cast to the appropriate type
     */
    public function get(User $user, string $key, mixed $default = null): mixed
    {
        $preferences = $this->loadUserPreferences($user);
        $preference = $preferences->firstWhere('key', $key);

        if ($preference !== null) {
            return $this->castValue($preference->value, $key);
        }

        return $this->settingsService->get($key, $default);
    }

    /**
     * Set a user's preference value.
     *
     * Only settings with scope='user' can be overridden. If the new
     * value matches the global default, the preference is deleted
     * instead (keeping storage clean). Clears the user's preference
     * cache after modification.
     *
     * @param  \Modules\Core\Models\User  $user  The user to set the preference for
     * @param  string  $key  The setting key to override
     * @param  mixed  $value  The preference value to store
     * @return void
     *
     * @throws \InvalidArgumentException When the setting is not user-overridable
     */
    public function set(User $user, string $key, mixed $value): void
    {
        if (! $this->settingsRegistry->isUserOverridable($key)) {
            throw new InvalidArgumentException(
                "Setting '{$key}' is not user-overridable. Only settings with scope='user' can be set as preferences."
            );
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $globalDefault = $this->settingsService->get($key);

        if (is_bool($globalDefault)) {
            $globalDefault = $globalDefault ? 'true' : 'false';
        }

        if ((string) $value === (string) $globalDefault) {
            UserPreference::where('user_id', $user->id)
                ->where('key', $key)
                ->delete();
        } else {
            UserPreference::updateOrCreate(
                ['user_id' => $user->id, 'key' => $key],
                ['value' => (string) $value]
            );
        }

        $this->clearUserCache($user);
    }

    /**
     * Set multiple preferences for a user in a single call.
     *
     * Each preference must be for a user-overridable setting.
     * Clears the user's preference cache once after all updates.
     *
     * @param  \Modules\Core\Models\User  $user  The user to set preferences for
     * @param  array<int, array{key: string, value: mixed}>  $preferences  Array of key-value pairs to set
     * @return void
     *
     * @throws \InvalidArgumentException When any setting is not user-overridable
     */
    public function setMany(User $user, array $preferences): void
    {
        foreach ($preferences as $pref) {
            $key = $pref['key'];
            $value = $pref['value'];

            if (! $this->settingsRegistry->isUserOverridable($key)) {
                throw new InvalidArgumentException(
                    "Setting '{$key}' is not user-overridable."
                );
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            UserPreference::updateOrCreate(
                ['user_id' => $user->id, 'key' => $key],
                ['value' => (string) $value]
            );
        }

        $this->clearUserCache($user);
    }

    /**
     * Reset a single user preference to the global default.
     *
     * Deletes the user's override for the given key, causing
     * future reads to fall back to the global setting value.
     *
     * @param  \Modules\Core\Models\User  $user  The user to reset the preference for
     * @param  string  $key  The setting key to reset
     * @return void
     */
    public function reset(User $user, string $key): void
    {
        UserPreference::where('user_id', $user->id)
            ->where('key', $key)
            ->delete();

        $this->clearUserCache($user);
    }

    /**
     * Reset all preferences for a user to global defaults.
     *
     * Deletes all user preference overrides. Clears the user's
     * preference cache after deletion.
     *
     * @param  \Modules\Core\Models\User  $user  The user to reset all preferences for
     * @return int The number of preferences deleted
     */
    public function resetAll(User $user): int
    {
        $deleted = UserPreference::where('user_id', $user->id)->delete();

        $this->clearUserCache($user);

        return $deleted;
    }

    /**
     * Get all settings that can be overridden by users.
     *
     * Returns the collection of settings registered with scope='user'
     * from the SettingsRegistry.
     *
     * @return \Illuminate\Support\Collection Collection of user-overridable setting definitions
     */
    public function getOverridableSettings(): Collection
    {
        return $this->settingsRegistry->getUserOverridableSettings();
    }

    /**
     * Get overridable settings with the user's current values.
     *
     * Merges the overridable setting definitions with the user's
     * actual preference values, indicating which have been customized
     * and what the global default is.
     *
     * @param  \Modules\Core\Models\User  $user  The user to retrieve settings for
     * @return \Illuminate\Support\Collection Collection with keys: key, label, type, group, module, value, is_custom, default_value
     */
    public function getOverridableSettingsForUser(User $user): Collection
    {
        $overridableSettings = $this->settingsRegistry->getUserOverridableSettings();
        $userPreferences = $this->loadUserPreferences($user)->keyBy('key');

        return $overridableSettings->map(function ($setting) use ($userPreferences) {
            $userPref = $userPreferences->get($setting['key']);

            return [
                'key' => $setting['key'],
                'label' => $setting['label'],
                'type' => $setting['type']->value ?? $setting['type'],
                'group' => $setting['group'],
                'module' => $setting['module'],
                'value' => $userPref ? $this->castValue($userPref->value, $setting['key']) : $setting['value'],
                'is_custom' => $userPref !== null,
                'default_value' => $setting['value'],
            ];
        });
    }

    /**
     * Check if a user has an active override for a setting.
     *
     * Returns true only if the user has an override AND the override
     * value differs from the current global default. If the values
     * match, the override is effectively a no-op.
     *
     * @param  \Modules\Core\Models\User  $user  The user to check
     * @param  string  $key  The setting key to check
     * @return bool True if the user has a meaningful override
     */
    public function hasOverride(User $user, string $key): bool
    {
        $preferences = $this->loadUserPreferences($user);
        $userPref = $preferences->firstWhere('key', $key);

        if ($userPref === null) {
            return false;
        }

        $globalDefault = $this->settingsService->get($key);
        $userValue = $this->normalizeForComparison($userPref->value, $key);
        $globalValue = $this->normalizeForComparison($globalDefault, $key);

        return $userValue !== $globalValue;
    }

    /**
     * Normalize a value for comparison based on its setting type.
     *
     * Converts values to their canonical type representation so that
     * string "true" and boolean true compare as equal.
     *
     * @param  mixed  $value  The value to normalize
     * @param  string  $key  The setting key for type lookup
     * @return mixed The normalized value
     */
    private function normalizeForComparison(mixed $value, string $key): mixed
    {
        if ($value === null) {
            return null;
        }

        $settingType = $this->settingsRegistry->getSettingType($key);

        if ($settingType === null) {
            return (string) $value;
        }

        return match ($settingType) {
            SettingType::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingType::Integer,
            SettingType::Currency => (int) $value,
            SettingType::Percentage => (float) $value,
            default => (string) $value,
        };
    }

    /**
     * Load all preferences for a user from cache or database.
     *
     * Uses tag-based caching with `rememberForever` to avoid
     * repeated database queries. Cache is tagged with both the
     * global preference tag and a user-specific tag for targeted
     * invalidation.
     *
     * @param  \Modules\Core\Models\User  $user  The user to load preferences for
     * @return \Illuminate\Support\Collection Collection of UserPreference models
     */
    private function loadUserPreferences(User $user): Collection
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$user->id;

        return $this->cacheService->rememberForever(
            $cacheKey,
            fn () => UserPreference::where('user_id', $user->id)->get(),
            [self::CACHE_TAG, 'user:'.$user->id]
        );
    }

    /**
     * Clear all cached preferences for a specific user.
     *
     * Uses the user-specific cache tag to invalidate only the
     * target user's preferences without affecting other users.
     *
     * @param  \Modules\Core\Models\User  $user  The user whose cache to clear
     * @return void
     */
    private function clearUserCache(User $user): void
    {
        $this->cacheService->clearTag('user:'.$user->id);
    }

    /**
     * Cast a preference value to its appropriate PHP type.
     *
     * Uses the setting's type definition from the SettingsRegistry
     * to convert stored string values to their correct types
     * (boolean, integer, float, etc.).
     *
     * @param  mixed  $value  The raw stored value
     * @param  string  $key  The setting key for type lookup
     * @return mixed The typed value
     */
    private function castValue(mixed $value, string $key): mixed
    {
        if ($value === null) {
            return null;
        }

        $settingType = $this->settingsRegistry->getSettingType($key);

        if ($settingType === null) {
            return $value;
        }

        return match ($settingType) {
            SettingType::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingType::Integer,
            SettingType::Currency => (int) $value,
            SettingType::Percentage => (float) $value,
            default => $value,
        };
    }
}
