<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Core\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Settings\Http\Requests\UpdateSettingsRequest;
use Modules\Settings\Models\Setting;
use Nwidart\Modules\Facades\Module;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService
    ) {}

    /**
     * Display all settings grouped by their 'group' column.
     *
     * Returns grouped settings for the UI to display in tabs.
     * Separates Application Settings (from Settings module) from Modules Settings.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Setting::class);

        $allSettings = $this->settingsService->all();

        $protectedModules = [];
        $allModules = Module::all();
        foreach ($allModules as $module) {
            if ($module->get('protected') === true) {
                $protectedModules[] = $module->getName();
            }
        }

        $applicationSettings = [];
        $modulesSettings = [];

        foreach ($allSettings as $group => $settings) {
            $isApplicationSetting = false;

            foreach ($settings as $setting) {
                if ($setting->module === 'Settings' || in_array($setting->module, $protectedModules, true)) {
                    $isApplicationSetting = true;
                    break;
                }
            }

            if ($isApplicationSetting) {
                $applicationSettings[$group] = $settings;
            } else {
                $modulesSettings[$group] = $settings;
            }
        }

        return Inertia::render('Modules::Settings/Index', [
            'applicationSettings' => $applicationSettings,
            'modulesSettings' => $modulesSettings,
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

        $appTab = null;
        $moduleTab = null;
        if ($request->headers->has('referer')) {
            $referer = parse_url($request->headers->get('referer'));
            if (isset($referer['query'])) {
                parse_str($referer['query'], $queryParams);
                $appTab = $queryParams['appTab'] ?? null;
                $moduleTab = $queryParams['moduleTab'] ?? null;
            }
        }

        $appTab = $appTab ?? $request->get('appTab');
        $moduleTab = $moduleTab ?? $request->get('moduleTab');

        $redirectUrl = route('settings.index');
        $queryParams = [];
        if ($appTab) {
            $queryParams[] = 'appTab='.urlencode($appTab);
        }
        if ($moduleTab) {
            $queryParams[] = 'moduleTab='.urlencode($moduleTab);
        }
        if (! empty($queryParams)) {
            $redirectUrl .= '?'.implode('&', $queryParams);
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
