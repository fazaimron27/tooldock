<?php

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
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('categories.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Categories', 'detail');

        return Inertia::render('Modules::Categories/Dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
