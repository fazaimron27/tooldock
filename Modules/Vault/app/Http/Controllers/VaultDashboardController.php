<?php

namespace Modules\Vault\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VaultDashboardController extends Controller
{
    /**
     * Display the Vault module dashboard.
     */
    public function index(DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('vaults.dashboard.view');

        $widgets = $widgetRegistry->getWidgetsForModule('Vault', 'detail');

        return Inertia::render('Modules::Vault/Dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
