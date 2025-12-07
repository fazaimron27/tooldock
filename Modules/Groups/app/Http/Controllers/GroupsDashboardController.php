<?php

namespace Modules\Groups\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GroupsDashboardController extends Controller
{
    /**
     * Display the Groups module dashboard.
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('groups.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Groups', 'detail');

        return Inertia::render('Modules::Groups/Dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
