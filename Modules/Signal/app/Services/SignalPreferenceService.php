<?php

/**
 * Signal Preference Service
 *
 * Checks user notification preferences before sending notifications.
 * Integrates with the settings system to respect user opt-outs.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Services;

use App\Services\Core\SettingsService;
use App\Services\Registry\SignalCategoryRegistry;
use Modules\Core\Models\User;

/**
 * Class SignalPreferenceService
 *
 * Determines if notifications should be sent based on user preferences.
 * Uses SignalCategoryRegistry to map categories to setting keys,
 * then checks the settings service for the current value.
 *
 * @see \App\Services\Registry\SignalCategoryRegistry For category registration
 */
class SignalPreferenceService
{
    /**
     * @param  SignalCategoryRegistry  $categoryRegistry  Category-to-setting mapping registry
     * @param  SettingsService  $settingsService  User settings service
     */
    public function __construct(
        private SignalCategoryRegistry $categoryRegistry,
        private SettingsService $settingsService
    ) {}

    /**
     * Check if notifications are enabled for a category.
     *
     * Returns true if the category is enabled or not registered
     * (defaults to allowing notifications). Categories without
     * registered setting keys are always enabled.
     *
     * @param  User  $user  The user to check preferences for
     * @param  string  $category  The notification category to check
     * @return bool True if notifications should be sent
     */
    public function isEnabled(User $user, string $category): bool
    {
        $settingKey = $this->categoryRegistry->getSettingKey($category);

        if ($settingKey === null) {
            return true;
        }

        try {
            $value = $this->settingsService->get($settingKey);

            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } catch (\Exception $e) {
            return true;
        }
    }
}
