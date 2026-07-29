<?php

/**
 * Folio Dashboard Controller.
 *
 * Displays the Folio module dashboard with widgets.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Folio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FolioDashboardController extends Controller
{
    /**
     * Display the Folio module dashboard.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @return Response
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('folio.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Folio', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Folio/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
