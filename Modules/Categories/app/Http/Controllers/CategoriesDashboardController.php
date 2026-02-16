<?php

/**
 * Categories Dashboard Controller.
 *
 * Displays the Categories module dashboard with registered widgets
 * and module metadata.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Categories\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesDashboardController extends Controller
{
    /**
     * Display the Categories module dashboard.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry  The widget registry service
     * @return Response
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('categories.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Categories', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Categories/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
