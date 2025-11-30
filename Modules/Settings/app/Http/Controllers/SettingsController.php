<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\UpdateSettingsRequest;
use Modules\Settings\Models\Setting;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {}

    /**
     * Display all settings grouped by their 'group' column.
     *
     * Returns grouped settings for the UI to display in tabs.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Setting::class);

        $settings = $this->settingsService->all();

        return Inertia::render('Modules::Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Handle bulk update of settings.
     *
     * Loops through the inputs and calls SettingsService->set() for each setting.
     * Handles individual failures gracefully, collecting errors for reporting.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $setting = Setting::first();

        if ($setting) {
            $this->authorize('update', $setting);
        }

        $errors = [];
        $successCount = 0;

        foreach ($request->validated() as $key => $value) {
            try {
                $this->settingsService->set($key, $value);
                $successCount++;
            } catch (\RuntimeException $e) {
                $errors[] = $key;
                Log::warning("SettingsController: Failed to update setting '{$key}'", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $tab = null;
        if ($request->headers->has('referer')) {
            $referer = parse_url($request->headers->get('referer'));
            if (isset($referer['query'])) {
                parse_str($referer['query'], $queryParams);
                $tab = $queryParams['tab'] ?? null;
            }
        }

        $tab = $tab ?? $request->get('tab');

        $redirectUrl = route('settings.index');
        if ($tab) {
            $redirectUrl .= '?tab='.urlencode($tab);
        }

        if (! empty($errors)) {
            $errorKeys = implode(', ', $errors);

            return redirect($redirectUrl)
                ->with('warning', "Settings updated with {$successCount} success(es), but the following could not be updated (may have been deleted): {$errorKeys}");
        }

        return redirect($redirectUrl)
            ->with('success', 'Settings updated successfully.');
    }
}
