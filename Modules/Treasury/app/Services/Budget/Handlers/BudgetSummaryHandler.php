<?php

namespace Modules\Treasury\Services\Budget\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Services\Budget\BudgetReportingService;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Budget Summary Handler
 *
 * Returns signal data for monthly budget summary (scheduled).
 */
class BudgetSummaryHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly BudgetReportingService $reportingService,
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['scheduled.monthly'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'BudgetSummaryHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return $data instanceof User;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data;

        $budgets = Budget::where('user_id', $user->id)->count();
        if ($budgets === 0) {
            return null;
        }

        $lastMonth = now()->subMonth();
        $month = (int) $lastMonth->format('m');
        $year = (int) $lastMonth->format('Y');
        $currency = settings('treasury_reference_currency', 'IDR');

        $summary = $this->reportingService->getMonthlySummary($user, $month, $year);

        $totalBudgets = $summary['categories_count'] ?? 0;
        $onTrack = $summary['safe_count'] ?? 0;
        $totalSpent = $summary['total_budgeted_spent'] ?? 0;

        if ($totalBudgets === 0) {
            return null;
        }

        $formattedSpent = $this->currencyFormatter->format($totalSpent, $currency);
        $monthName = $lastMonth->format('F');

        Log::info('BudgetSummaryHandler: Monthly summary', [
            'user_id' => $user->id,
            'month' => $monthName,
        ]);

        return [
            'type' => 'info',
            'title' => "{$monthName} Budget Summary",
            'message' => "{$onTrack} of {$totalBudgets} budgets remained on track. Total spending: {$formattedSpent}.",
            'url' => route('treasury.budgets.index'),
            'category' => 'treasury_budget',
        ];
    }
}
