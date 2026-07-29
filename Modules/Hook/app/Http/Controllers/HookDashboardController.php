<?php

/**
 * Hook Dashboard Controller.
 *
 * Displays the Hook module dashboard with widgets.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Hook\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HookDashboardController extends Controller
{
    /**
     * Display the Hook module dashboard.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @return Response
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('hook.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Hook', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Hook/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
