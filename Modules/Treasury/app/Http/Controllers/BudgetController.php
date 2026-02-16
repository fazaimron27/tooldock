<?php

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Categories\Models\Category;
use Modules\Treasury\Http\Requests\StoreBudgetRequest;
use Modules\Treasury\Http\Requests\UpdateBudgetRequest;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Modules\Treasury\Services\Budget\BudgetPeriodService;
use Modules\Treasury\Services\Budget\BudgetReportingService;

class BudgetController extends Controller
{
    private const CACHE_TTL_HOURS = 24;

    private const CACHE_TAG = 'treasury';

    public function __construct(
        private readonly BudgetReportingService $reportingService,
        private readonly BudgetPeriodService $periodService,
        private readonly CacheService $cacheService
    ) {}

    /**
     * Display a listing of budgets for a specific month.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Budget::class);

        $user = Auth::user();

        // Get month/year from request or default to current
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Validate month/year
        $month = max(1, min(12, (int) $month));
        $year = max(2020, min(2100, (int) $year));

        $currentDate = Carbon::create($year, $month, 1);

        // Get budget templates
        $budgets = Budget::forUser()
            ->with('category')
            ->get();

        // Get monthly report and summary
        $report = $this->reportingService->getMonthlyReport($user, $month, $year);
        $summary = $this->reportingService->getMonthlySummary($user, $month, $year);

        // Get available periods for navigation
        $availablePeriods = $this->periodService->getAvailablePeriods($user);

        return Inertia::render('Modules::Treasury/Budgets/Index', [
            'budgets' => $budgets,
            'report' => $report,
            'summary' => $summary,
            'currentMonth' => $currentDate->format('F Y'),
            'currentPeriod' => [
                'month' => $month,
                'year' => $year,
                'period' => BudgetPeriod::formatPeriod($month, $year),
            ],
            'navigation' => [
                'previous' => [
                    'month' => $currentDate->copy()->subMonth()->month,
                    'year' => $currentDate->copy()->subMonth()->year,
                ],
                'next' => [
                    'month' => $currentDate->copy()->addMonth()->month,
                    'year' => $currentDate->copy()->addMonth()->year,
                ],
                'isCurrentMonth' => $currentDate->isSameMonth(now()),
            ],
            'availablePeriods' => $availablePeriods,
        ]);
    }

    /**
     * Show the form for creating a new budget template.
     */
    public function create(): Response
    {
        $this->authorize('create', Budget::class);

        // Get all transaction categories
        $categories = $this->getCategories();

        // Get category IDs that already have budgets (one budget per category)
        $usedCategoryIds = Budget::forUser()
            ->pluck('category_id')
            ->toArray();

        return Inertia::render('Modules::Treasury/Budgets/Create', [
            'categories' => $categories,
            'usedCategoryIds' => $usedCategoryIds,
        ]);
    }

    /**
     * Store a newly created budget template.
     */
    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Default currency to user's reference currency if not provided
        $currency = $request->validated('currency')
            ?? settings('treasury_reference_currency', 'IDR');

        Budget::create([
            'user_id' => $user->id,
            'category_id' => $request->validated('category_id'),
            'amount' => $request->validated('amount'),
            'currency' => $currency,
            'is_active' => true,
            'is_recurring' => $request->validated('is_recurring', true),
            'rollover_enabled' => $request->validated('rollover_enabled', false),
        ]);

        return redirect()
            ->route('treasury.budgets.index')
            ->with('success', 'Budget template created successfully.');
    }

    /**
     * Show the form for editing the specified budget template.
     */
    public function edit(Request $request, Budget $budget): Response
    {
        $this->authorize('update', $budget);

        $budget->load('category');

        $hasExplicitPeriod = $request->has('month') && $request->has('year');

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $period = BudgetPeriod::formatPeriod($month, $year);

        $budgetPeriod = null;
        if ($hasExplicitPeriod) {
            $budgetPeriod = BudgetPeriod::where('budget_id', $budget->id)
                ->where('period', $period)
                ->first();
        }

        $categories = $this->getCategories();

        return Inertia::render('Modules::Treasury/Budgets/Edit', [
            'budget' => $budget,
            'budgetPeriod' => $budgetPeriod,
            'categories' => $categories,
            'isEditingPeriod' => $hasExplicitPeriod,
            'currentPeriod' => [
                'month' => $month,
                'year' => $year,
                'period' => $period,
                'label' => Carbon::create($year, $month)->format('F Y'),
                'isExplicit' => $hasExplicitPeriod,
            ],
        ]);
    }

    /**
     * Update the specified budget template.
     */
    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->has('update_type') && $request->input('update_type') === 'period') {
            $periodString = $request->input('period');
            [$year, $month] = explode('-', $periodString);

            $budgetPeriod = $this->periodService->getOrCreatePeriod(
                $budget,
                (int) $month,
                (int) $year
            );

            $this->periodService->updatePeriodAmount(
                $budgetPeriod,
                (float) $validated['amount'],
                $validated['description'] ?? null
            );

            $message = 'Budget for this month updated successfully.';
        } else {
            $budget->update([
                'amount' => $validated['amount'] ?? $budget->amount,
                'is_recurring' => $validated['is_recurring'] ?? $budget->is_recurring,
                'rollover_enabled' => $validated['rollover_enabled'] ?? $budget->rollover_enabled,
            ]);

            $message = 'Budget template updated successfully.';
        }

        return redirect()
            ->route('treasury.budgets.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified budget template.
     */
    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorize('delete', $budget);
        $budget->delete();

        return redirect()
            ->route('treasury.budgets.index')
            ->with('success', 'Budget deleted successfully.');
    }

    /**
     * Deactivate a budget template.
     */
    public function deactivate(Budget $budget): RedirectResponse
    {
        $this->authorize('update', $budget);
        $budget->update(['is_active' => false]);

        return redirect()
            ->route('treasury.budgets.index')
            ->with('success', 'Budget deactivated.');
    }

    /**
     * Reactivate a budget template.
     */
    public function reactivate(Budget $budget): RedirectResponse
    {
        $this->authorize('update', $budget);
        $budget->update(['is_active' => true]);

        return redirect()
            ->route('treasury.budgets.index')
            ->with('success', 'Budget reactivated.');
    }

    /**
     * Get cached transaction categories.
     */
    private function getCategories()
    {
        return $this->cacheService->remember(
            'treasury:categories:transaction',
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Category::where('type', 'transaction_category')->orderBy('name')->get(),
            self::CACHE_TAG,
            'BudgetController'
        );
    }
}
