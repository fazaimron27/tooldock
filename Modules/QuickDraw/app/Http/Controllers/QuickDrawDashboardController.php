<?php

/**
 * QuickDraw Dashboard Controller.
 *
 * Displays the QuickDraw module dashboard with widgets.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\QuickDraw\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuickDrawDashboardController extends Controller
{
    /**
     * Display the QuickDraw module dashboard.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @return Response
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('quickdraw.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('QuickDraw', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::QuickDraw/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
