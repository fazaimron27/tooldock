<?php

/**
 * Settings Dashboard Controller.
 *
 * Displays the Settings module dashboard with widgets.
 *
 * @author Tool Dock Team
 * @license MIT
 */

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
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @return Response
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('settings.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Settings', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Settings/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
