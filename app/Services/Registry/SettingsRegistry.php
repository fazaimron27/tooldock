<?php

/**
 * Settings Registry
 *
 * Manages application settings registration for modules,
 * supporting scoped settings (global and user-overridable),
 * database seeding, and Redis cache invalidation.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

use App\Services\Cache\CacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Settings\Enums\SettingType;
use Modules\Settings\Models\Setting;

/**
 * Registry for managing application settings registration.
 *
 * Allows modules to register their settings during service provider boot,
 * which are then automatically seeded into the database.
 *
 * Optimized for Redis with cache tags for efficient invalidation.
 * Group name convention: Use lowercase module name (e.g., 'blog' for Blog module).
 * This ensures proper cleanup when modules are uninstalled.
 */
class SettingsRegistry
{
    private const CACHE_TAG = 'settings';

    /**
     * @var array<int, array{module: string, group: string, key: string, value: mixed, type: SettingType, label: string, is_system: bool, options: ?array, searchable: bool}>
     */
    private array $settings = [];

    public function __construct(
        private CacheService $cacheService
    ) {}

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
     * @param  SettingType  $type  Setting type (Text, Boolean, Integer, Textarea, Select)
     * @param  string  $label  Human-readable label for the UI
     * @param  bool  $isSystem  Whether this is a system setting (not editable via UI)
     * @param  array|null  $options  Options for select-type settings (array of ['value' => '', 'label' => ''])
     * @param  bool  $searchable  Whether select-type settings should have search functionality
     * @param  string|null  $category  Category key for grouping related settings in UI
     * @param  string|null  $categoryLabel  Human-readable label for the category card
     * @param  string|null  $categoryDescription  Description shown in the category card
     * @param  string  $scope  Setting scope: 'global' (admin-only) or 'user' (user-overridable)
     * @param  string|null  $permission  Permission required to access this setting's preferences
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
        bool $isSystem = false,
        ?array $options = null,
        bool $searchable = false,
        ?string $category = null,
        ?string $categoryLabel = null,
        ?string $categoryDescription = null,
        string $scope = 'global',
        ?string $permission = null
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
            'options' => $options,
            'searchable' => $searchable,
            'category' => $category,
            'category_label' => $categoryLabel,
            'category_description' => $categoryDescription,
            'scope' => $scope,
            'permission' => $permission,
        ];
    }

    /**
     * Register multiple settings for a module in a single call.
     *
     * Uses grouped category format:
     * ['category_key' => ['label' => '...', 'description' => '...', 'settings' => [...]]]
     *
     * @param  string  $module  Module name
     * @param  string  $group  Setting group name
     * @param  array  $categories  Array of category definitions with their settings
     */
    public function registerMany(string $module, string $group, array $categories): void
    {
        foreach ($categories as $categoryKey => $categoryConfig) {
            $categoryLabel = $categoryConfig['label'] ?? null;
            $categoryDescription = $categoryConfig['description'] ?? null;
            $categoryPermission = $categoryConfig['permission'] ?? null;
            $categorySettings = $categoryConfig['settings'] ?? [];

            foreach ($categorySettings as $setting) {
                $this->register(
                    module: $module,
                    group: $group,
                    key: $setting['key'],
                    value: $setting['value'],
                    type: $setting['type'],
                    label: $setting['label'],
                    isSystem: $setting['is_system'] ?? false,
                    options: $setting['options'] ?? null,
                    searchable: $setting['searchable'] ?? false,
                    category: $categoryKey,
                    categoryLabel: $categoryLabel,
                    categoryDescription: $categoryDescription,
                    scope: $setting['scope'] ?? 'global',
                    permission: $categoryPermission
                );
            }
        }
    }

    /**
     * Get all registered settings.
     *
     * @return array<int, array{module: string, group: string, key: string, value: mixed, type: SettingType, label: string, is_system: bool, options: ?array, searchable: bool}>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Get settings for a specific module.
     *
     * @param  string  $module  Module name
     * @return array<int, array{module: string, group: string, key: string, value: mixed, type: SettingType, label: string, is_system: bool, options: ?array, searchable: bool}>
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
     * Uses bulk operations for optimal performance.
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
            try {
                $registeredKeys = array_column($this->settings, 'key');
                $existingSettings = Setting::whereIn('key', $registeredKeys)
                    ->get()
                    ->keyBy('key');
                $toInsert = [];
                $toUpdate = [];
                $typeChanges = [];
                $now = now();

                foreach ($this->settings as $setting) {
                    $existing = $existingSettings->get($setting['key']);

                    if ($existing) {
                        $typeChanged = $existing->type !== $setting['type'];

                        if ($typeChanged) {
                            $typeChanges[] = [
                                'key' => $setting['key'],
                                'old_type' => $existing->type->value,
                                'new_type' => $setting['type']->value,
                                'module' => $setting['module'],
                            ];
                        }

                        $toUpdate[] = [
                            'id' => $existing->id,
                            'module' => $setting['module'],
                            'group' => $setting['group'],
                            'type' => $setting['type'],
                            'label' => $setting['label'],
                            'is_system' => $setting['is_system'],
                            'options' => $setting['options'],
                            'searchable' => $setting['searchable'],
                            'category' => $setting['category'],
                            'category_label' => $setting['category_label'],
                            'category_description' => $setting['category_description'],
                            'scope' => $setting['scope'] ?? 'global',
                            'value' => $typeChanged ? $setting['value'] : null,
                            'reset_value' => $typeChanged,
                            'updated_at' => $now,
                        ];
                    } else {
                        $toInsert[] = [
                            'id' => (string) Str::orderedUuid(),
                            'module' => $setting['module'],
                            'group' => $setting['group'],
                            'key' => $setting['key'],
                            'value' => is_array($setting['value']) ? json_encode($setting['value']) : $setting['value'],
                            'type' => $setting['type'],
                            'label' => $setting['label'],
                            'is_system' => $setting['is_system'],
                            'options' => $setting['options'] ? json_encode($setting['options']) : null,
                            'searchable' => $setting['searchable'],
                            'category' => $setting['category'],
                            'category_label' => $setting['category_label'],
                            'category_description' => $setting['category_description'],
                            'scope' => $setting['scope'] ?? 'global',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                $created = 0;
                if (! empty($toInsert)) {
                    Setting::insert($toInsert);
                    $created = count($toInsert);
                }

                $updated = 0;
                if (! empty($toUpdate)) {
                    foreach ($toUpdate as $updateData) {
                        $id = $updateData['id'];
                        $resetValue = $updateData['reset_value'];
                        unset($updateData['id'], $updateData['reset_value']);

                        if (! $resetValue) {
                            unset($updateData['value']);
                        } else {
                            if (is_array($updateData['value'])) {
                                $updateData['value'] = json_encode($updateData['value']);
                            }
                        }
                        if (is_array($updateData['options'])) {
                            $updateData['options'] = json_encode($updateData['options']);
                        }

                        Setting::where('id', $id)->update($updateData);
                        $updated++;
                    }
                }

                foreach ($typeChanges as $change) {
                    Log::info("SettingsRegistry: Type changed for setting '{$change['key']}', resetting to default value", [
                        'module' => $change['module'],
                        'old_type' => $change['old_type'],
                        'new_type' => $change['new_type'],
                    ]);
                }

                if ($created > 0 || $updated > 0) {
                    Log::debug('SettingsRegistry: Seeding completed', [
                        'created' => $created,
                        'updated' => $updated,
                        'type_changes' => count($typeChanges),
                        'total' => count($this->settings),
                    ]);

                    $this->clearCache();
                }
            } catch (\Exception $e) {
                Log::error('SettingsRegistry: Seeding failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                if ($strict) {
                    throw $e;
                }
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

                $this->clearCache();
            } else {
                Log::info("SettingsRegistry: No settings found for module '{$moduleName}'");
            }

            return [
                'deleted' => $deleted,
            ];
        });
    }

    /**
     * Clear all settings caches.
     *
     * Optimized for Redis - uses tag-based flush for efficient invalidation.
     * This method is called automatically when settings are seeded or cleaned up,
     * ensuring cache consistency without manual intervention.
     */
    private function clearCache(): void
    {
        $this->cacheService->clearTag(self::CACHE_TAG, 'SettingsRegistry');
    }

    /**
     * Check if a setting is user-overridable.
     *
     * @param  string  $key  The setting key
     * @return bool True if the setting can be overridden by users
     */
    public function isUserOverridable(string $key): bool
    {
        foreach ($this->settings as $setting) {
            if ($setting['key'] === $key) {
                return ($setting['scope'] ?? 'global') === 'user';
            }
        }

        return false;
    }

    /**
     * Get all settings that can be overridden by users.
     *
     * @return \Illuminate\Support\Collection Collection of user-overridable settings
     */
    public function getUserOverridableSettings(): Collection
    {
        return collect($this->settings)->filter(
            fn ($setting) => ($setting['scope'] ?? 'global') === 'user'
        );
    }

    /**
     * Get the type of a setting.
     *
     * @param  string  $key  The setting key
     * @return SettingType|null The setting type, or null if not found
     */
    public function getSettingType(string $key): ?SettingType
    {
        foreach ($this->settings as $setting) {
            if ($setting['key'] === $key) {
                return $setting['type'];
            }
        }

        return null;
    }
}
