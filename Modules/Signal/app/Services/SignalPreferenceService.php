<?php

/**
 * Signal Preference Service
 *
 * Checks user notification preferences before sending notifications.
 * Integrates with the user preferences system to respect individual user opt-outs.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Services;

use App\Services\Core\UserPreferenceService;
use App\Services\Registry\SignalCategoryRegistry;
use Modules\Core\Models\User;

/**
 * Class SignalPreferenceService
 *
 * Determines if notifications should be sent based on user preferences.
 * Uses SignalCategoryRegistry to map categories to setting keys,
 * then checks the user preferences service for the current value.
 *
 * Now uses per-user preferences instead of global settings,
 * allowing each user to control their own notification preferences.
 *
 * @see \App\Services\Registry\SignalCategoryRegistry For category registration
 * @see \App\Services\Core\UserPreferenceService For per-user preference management
 */
class SignalPreferenceService
{
    /**
     * @param  SignalCategoryRegistry  $categoryRegistry  Category-to-setting mapping registry
     * @param  UserPreferenceService  $userPreferenceService  Per-user preferences service
     */
    public function __construct(
        private SignalCategoryRegistry $categoryRegistry,
        private UserPreferenceService $userPreferenceService
    ) {}

    /**
     * Check if notifications are enabled for a category.
     *
     * Returns true if the category is enabled or not registered
     * (defaults to allowing notifications). Categories without
     * registered setting keys are always enabled.
     *
     * Uses the user preferences system which checks for user-specific
     * overrides first, then falls back to global settings if the user
     * hasn't customized their preference.
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
            $value = $this->userPreferenceService->get($user, $settingKey);

            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } catch (\Exception $e) {
            return true;
        }
    }
}
