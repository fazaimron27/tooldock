<?php

/**
 * Vault Dashboard Controller
 *
 * Handles the Vault module dashboard page that displays
 * module-specific widgets and statistics.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class VaultDashboardController
 *
 * Renders the Vault module dashboard with registered widgets and metadata.
 *
 * @see \Modules\Vault\Services\VaultDashboardService
 */
class VaultDashboardController extends Controller
{
    /**
     * Display the Vault module dashboard.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry  The widget registry for resolving module widgets
     * @return Response Inertia response rendering the vault dashboard
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('vaults.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Vault', 'detail');
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        return Inertia::render('Modules::Vault/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
        ]);
    }
}
