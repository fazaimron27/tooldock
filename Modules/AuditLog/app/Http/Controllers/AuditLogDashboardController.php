<?php

/**
 * Audit Log Dashboard Controller.
 *
 * Renders the AuditLog module's dedicated dashboard page
 * with registered widgets and module metadata.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\AuditLog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Audit Log Dashboard Controller.
 *
 * Renders the AuditLog module's dedicated dashboard with registered widgets.
 */
class AuditLogDashboardController extends Controller
{
    /**
     * Display the AuditLog module dashboard.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry  The widget registry for retrieving module widgets
     * @return Response Inertia response rendering the dashboard page
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('auditlog.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('AuditLog', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::AuditLog/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
