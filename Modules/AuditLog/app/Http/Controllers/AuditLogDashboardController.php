<?php

namespace Modules\AuditLog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogDashboardController extends Controller
{
    /**
     * Display the AuditLog module dashboard.
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('auditlog.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('AuditLog', 'detail');

        return Inertia::render('Modules::AuditLog/Dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
