<?php

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Treasury\Services\TreasuryOverviewService;

class TreasuryOverviewController extends Controller
{
    public function __construct(
        private readonly TreasuryOverviewService $overviewService
    ) {}

    /**
     * Display the Treasury main index page (Overview).
     */
    public function index(): Response
    {
        Gate::authorize('treasuries.treasury.view');

        return Inertia::render('Modules::Treasury/Index', $this->overviewService->getOverviewData());
    }
}
