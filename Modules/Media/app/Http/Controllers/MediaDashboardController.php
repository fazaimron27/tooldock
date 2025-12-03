<?php

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
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('media.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Media', 'detail');

        return Inertia::render('Modules::Media/Dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
