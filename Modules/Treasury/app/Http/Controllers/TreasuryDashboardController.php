<?php

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TreasuryDashboardController extends Controller
{
    /**
     * Display the Treasury module dashboard.
     */
    public function index(\Illuminate\Http\Request $request, DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('treasuries.dashboard.view');

        $filters = $request->only(['wallet_id', 'date_from', 'date_to']);
        $widgets = $widgetRegistry->getWidgetsForModule('Treasury', 'detail', $filters);
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        $wallets = \Modules\Treasury\Models\Wallet::where('user_id', Auth::id())
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        return Inertia::render('Modules::Treasury/Dashboard', [
            'widgets' => $widgets,
            'moduleMetadata' => $moduleMetadata,
            'filters' => $filters,
            'availableWallets' => $wallets,
        ]);
    }
}
