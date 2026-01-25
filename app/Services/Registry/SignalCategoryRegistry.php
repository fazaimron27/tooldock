<?php

/**
 * Signal Category Registry
 *
 * Registry for Signal notification category preferences.
 * Allows modules to register their notification categories
 * with corresponding settings keys for user preference checking.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

/**
 * Class SignalCategoryRegistry
 *
 * Central registry for notification category to settings key mappings.
 * Modules register their categories during boot, and the Signal module
 * queries this registry to check user preferences before sending.
 *
 * Built-in categories (login, security, system) are registered by Signal.
 * Other modules can register their own (e.g., 'vault' => 'vault_notify_enabled').
 *
 * @see \Modules\Signal\Services\SignalPreferenceService For preference checking
 */
class SignalCategoryRegistry
{
    /**
     * Category to setting key mappings.
     *
     * @var array<string, string>
     */
    private array $categories = [];

    /**
     * Track which module registered each category.
     *
     * @var array<string, string>
     */
    private array $registeredBy = [];

    /**
     * Register a notification category with its setting key.
     *
     * Associates a notification category with a user setting key that
     * controls whether notifications of that category should be sent.
     *
     * @param  string  $module  The module registering this category
     * @param  string  $category  The notification category name
     * @param  string  $settingKey  The setting key to check for preferences
     * @return void
     *
     * @throws \RuntimeException When duplicate category is registered by different module
     */
    public function register(string $module, string $category, string $settingKey): void
    {
        $category = strtolower($category);

        if (isset($this->registeredBy[$category])) {
            if ($this->registeredBy[$category] !== $module) {
                throw new \RuntimeException(
                    "Signal category '{$category}' is already registered by module '{$this->registeredBy[$category]}'. ".
                        "Module '{$module}' cannot register a duplicate category."
                );
            }

            $this->categories[$category] = $settingKey;

            return;
        }

        $this->categories[$category] = $settingKey;
        $this->registeredBy[$category] = $module;
    }

    /**
     * Get the setting key for a category.
     *
     * Returns null if the category is not registered.
     *
     * @param  string  $category  The category to look up
     * @return string|null The setting key or null if not found
     */
    public function getSettingKey(string $category): ?string
    {
        return $this->categories[strtolower($category)] ?? null;
    }

    /**
     * Check if a category is registered.
     *
     * @param  string  $category  The category to check
     * @return bool True if category is registered
     */
    public function has(string $category): bool
    {
        return isset($this->categories[strtolower($category)]);
    }

    /**
     * Get all registered categories.
     *
     * Returns an associative array of category => setting key mappings.
     *
     * @return array<string, string> All registered categories
     */
    public function getAll(): array
    {
        return $this->categories;
    }

    /**
     * Get categories registered by a specific module.
     *
     * Filters categories to only return those registered by the
     * specified module name.
     *
     * @param  string  $module  The module name to filter by
     * @return array<string, string> Categories registered by the module
     */
    public function getByModule(string $module): array
    {
        $result = [];
        foreach ($this->registeredBy as $category => $registeredModule) {
            if ($registeredModule === $module) {
                $result[$category] = $this->categories[$category];
            }
        }

        return $result;
    }

    /**
     * Clear all registered categories.
     *
     * Primarily useful for testing to reset registry state.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->categories = [];
        $this->registeredBy = [];
    }
}
