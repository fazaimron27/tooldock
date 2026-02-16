<?php

/**
 * Media Dashboard Controller.
 *
 * Displays the Media module dashboard with widgets.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MediaDashboardController extends Controller
{
    /**
     * Display the Media module dashboard.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @return Response
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('media.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Media', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Media/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
