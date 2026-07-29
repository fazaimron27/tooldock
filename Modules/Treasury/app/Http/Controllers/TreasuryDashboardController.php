<?php

/**
 * Treasury Dashboard Controller
 *
 * Renders the Treasury module dashboard with registered widgets and
 * active wallet filters. Delegates widget resolution to the
 * DashboardWidgetRegistry for modular, extensible dashboard composition.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Treasury\Models\Wallet;

/**
 * Class TreasuryDashboardController
 *
 * Displays the Treasury module dashboard with widgets and filters.
 */
class TreasuryDashboardController extends Controller
{
    /**
     * Display the Treasury module dashboard.
     *
     * @param  Request  $request  The incoming request
     * @param  DashboardWidgetRegistry  $widgetRegistry  The widget registry
     * @return Response
     */
    public function index(Request $request, DashboardWidgetRegistry $widgetRegistry): Response
    {
        Gate::authorize('treasuries.dashboard.view');

        $filters = $request->only(['wallet_id', 'date_from', 'date_to']);
        $widgets = $widgetRegistry->getWidgetsForModule('Treasury', 'detail', $filters);
        $moduleMetadata = $widgetRegistry->getAllModuleMetadata();

        $wallets = Wallet::where('user_id', Auth::id())
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
