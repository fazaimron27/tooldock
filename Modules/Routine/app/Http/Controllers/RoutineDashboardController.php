<?php

/**
 * Routine Dashboard Controller
 *
 * Renders the Routine module dashboard with registered widgets.
 * Delegates widget resolution to the DashboardWidgetRegistry
 * for modular, extensible dashboard composition.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class RoutineDashboardController
 *
 * Displays the Routine module dashboard with widgets.
 */
class RoutineDashboardController extends Controller
{
    /**
     * Display the Routine module dashboard.
     *
     * @param  Request  $request  The incoming request
     * @param  DashboardWidgetRegistry  $widgetRegistry  The widget registry
     * @return Response
     */
    public function index(Request $request, DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('routines.dashboard.view');

        $filters = $request->only(['date_from', 'date_to']);
        $widgets = $widgetRegistry->getWidgetsForModule('Routine', 'detail', $filters);
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Routine/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
            'filters' => $filters,
        ]);
    }
}
