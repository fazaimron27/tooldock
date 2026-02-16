<?php

/**
 * Treasury Overview Controller
 *
 * Displays the Treasury main overview page with aggregated financial data
 * including wallet summaries, recent transactions, budget progress, and
 * goal tracking. Delegates data composition to TreasuryOverviewService.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Treasury\Services\TreasuryOverviewService;

/**
 * Class TreasuryOverviewController
 *
 * Displays the Treasury main overview page.
 */
class TreasuryOverviewController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  TreasuryOverviewService  $overviewService  The overview data service
     */
    public function __construct(
        private readonly TreasuryOverviewService $overviewService
    ) {}

    /**
     * Display the Treasury main index page (Overview).
     *
     * @return Response
     */
    public function index(): Response
    {
        Gate::authorize('treasuries.treasury.view');

        return Inertia::render('Modules::Treasury/Index', $this->overviewService->getOverviewData());
    }
}
