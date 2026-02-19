<?php

/**
 * User Preference Controller
 *
 * Handles user preference management for overridable settings.
 * Users can customize their own notification and security preferences.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Core\UserPreferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Class UserPreferenceController
 *
 * Manages user preference operations including viewing and updating
 * user-overridable settings. Uses the hybrid preferences system that
 * falls back to global settings when no user override exists.
 *
 * @see \App\Services\Core\UserPreferenceService For preference management
 */
class UserPreferenceController extends Controller
{
    public function __construct(
        private UserPreferenceService $preferenceService
    ) {}

    /**
     * Display the user's preferences page.
     *
     * Shows user-overridable settings grouped by category,
     * filtered to only include settings the user has access to.
     *
     * @param  Request  $request  The HTTP request
     * @return Response Inertia preferences page
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $overridableSettings = $this->preferenceService->getOverridableSettings();

        $preferences = [];
        $grouped = [];

        foreach ($overridableSettings as $setting) {
            if (! $this->userHasSettingAccess($user, $setting)) {
                continue;
            }

            $key = $setting['key'];
            $currentValue = $this->preferenceService->get($user, $key);
            $hasOverride = $this->preferenceService->hasOverride($user, $key);

            $preference = [
                'key' => $key,
                'label' => $setting['label'],
                'type' => $setting['type']->value,
                'value' => $currentValue,
                'hasOverride' => $hasOverride,
                'module' => $setting['module'],
                'group' => $setting['group'],
                'category' => $setting['category'] ?? $setting['group'],
                'categoryLabel' => $setting['category_label'] ?? ucfirst($setting['group']),
                'categoryDescription' => $setting['category_description'] ?? null,
                'options' => $setting['options'] ?? null,
            ];

            $preferences[] = $preference;

            $category = $preference['category'];
            if (! isset($grouped[$category])) {
                $grouped[$category] = [
                    'label' => $preference['categoryLabel'],
                    'description' => $preference['categoryDescription'],
                    'settings' => [],
                ];
            }
            $grouped[$category]['settings'][] = $preference;
        }

        return Inertia::render('Profile/Preferences', [
            'preferences' => $preferences,
            'grouped' => $grouped,
        ]);
    }

    /**
     * Check if user has access to a specific setting.
     *
     * Reads the permission from the setting's metadata.
     * Uses Gate::allows() to respect Gate::before() superadmin bypass.
     *
     * @param  \Modules\Core\Models\User  $user
     * @param  array  $setting  The setting array containing permission key
     * @return bool
     */
    private function userHasSettingAccess($user, array $setting): bool
    {
        $permission = $setting['permission'] ?? null;

        if ($permission === null) {
            return true;
        }

        return Gate::forUser($user)->allows($permission);
    }

    /**
     * Update a single user preference.
     *
     * Sets a user-specific override for a setting.
     * User must have access to the module the setting belongs to.
     *
     * @param  Request  $request  The HTTP request with key and value
     * @return RedirectResponse Redirect back with success message
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
            'value' => ['required'],
        ]);

        $user = $request->user();
        $key = $request->input('key');
        $value = $request->input('value');

        $setting = $this->findSettingByKey($key);
        if ($setting && ! $this->userHasSettingAccess($user, $setting)) {
            return Redirect::back()->withErrors(['key' => 'You do not have permission to modify this preference.']);
        }

        try {
            $this->preferenceService->set($user, $key, $value);

            return Redirect::back()->with('success', 'Preference updated successfully.');
        } catch (InvalidArgumentException $e) {
            return Redirect::back()->withErrors(['key' => $e->getMessage()]);
        }
    }

    /**
     * Find a setting by its key.
     *
     * @param  string  $key
     * @return array|null
     */
    private function findSettingByKey(string $key): ?array
    {
        $settings = $this->preferenceService->getOverridableSettings();

        foreach ($settings as $setting) {
            if ($setting['key'] === $key) {
                return $setting;
            }
        }

        return null;
    }

    /**
     * Update multiple user preferences at once.
     *
     * Bulk update for saving all preferences from the form.
     *
     * @param  Request  $request  The HTTP request with preferences array
     * @return RedirectResponse Redirect back with success message
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.key' => ['required', 'string'],
            'preferences.*.value' => ['required'],
        ]);

        $user = $request->user();
        $preferences = $request->input('preferences');

        foreach ($preferences as $pref) {
            $setting = $this->findSettingByKey($pref['key']);
            if ($setting && ! $this->userHasSettingAccess($user, $setting)) {
                return Redirect::back()->withErrors(['preferences' => 'You do not have permission to modify some preferences.']);
            }
        }

        try {
            $this->preferenceService->setMany($user, $preferences);

            return Redirect::back()->with('success', 'Preferences updated successfully.');
        } catch (InvalidArgumentException $e) {
            return Redirect::back()->withErrors(['preferences' => $e->getMessage()]);
        }
    }

    /**
     * Reset a user preference to use global default.
     *
     * Removes the user override, falling back to global setting.
     * User must have access to the module the setting belongs to.
     *
     * @param  Request  $request  The HTTP request with key
     * @return RedirectResponse Redirect back with success message
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
        ]);

        $user = $request->user();
        $key = $request->input('key');

        $setting = $this->findSettingByKey($key);
        if ($setting && ! $this->userHasSettingAccess($user, $setting)) {
            return Redirect::back()->withErrors(['key' => 'You do not have permission to modify this preference.']);
        }

        $this->preferenceService->reset($user, $key);

        return Redirect::back()->with('success', 'Preference reset to default.');
    }

    /**
     * Reset all user preferences to use global defaults.
     *
     * Removes all user overrides for the authenticated user.
     *
     * @param  Request  $request  The HTTP request
     * @return RedirectResponse Redirect back with success message
     */
    public function resetAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->preferenceService->resetAll($user);

        return Redirect::back()->with('success', 'All preferences reset to defaults.');
    }
}
