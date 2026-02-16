<?php

/**
 * Groups Dashboard Controller.
 *
 * Handles rendering the Groups module dashboard page.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for the Groups module dashboard.
 */
class GroupsDashboardController extends Controller
{
    /**
     * Display the Groups module dashboard.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @return Response
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('groups.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Groups', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Groups/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
