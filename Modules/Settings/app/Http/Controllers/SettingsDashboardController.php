<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingsDashboardController extends Controller
{
    /**
     * Display the Settings module dashboard.
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('settings.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Settings', 'detail');

        return Inertia::render('Modules::Settings/Dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
