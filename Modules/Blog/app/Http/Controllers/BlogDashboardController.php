<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BlogDashboardController extends Controller
{
    /**
     * Display the Blog module dashboard.
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('blog.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Blog', 'detail');

        return Inertia::render('Modules::Blog/Dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
