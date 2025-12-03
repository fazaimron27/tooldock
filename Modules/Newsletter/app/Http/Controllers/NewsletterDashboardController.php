<?php

namespace Modules\Newsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterDashboardController extends Controller
{
    /**
     * Display the Newsletter module dashboard.
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('newsletter.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Newsletter', 'detail');

        return Inertia::render('Modules::Newsletter/Dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
