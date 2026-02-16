<?php

/**
 * Budget Threshold Handler
 *
 * Signal handler that returns data when a budget exceeds warning or
 * overbudget thresholds. Triggers on transaction and budget changes.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Budget\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Budget\BudgetReportingService;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Budget Threshold Handler
 *
 * Returns signal data when budget exceeds warning or overbudget thresholds.
 */
class BudgetThresholdHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly BudgetReportingService $reportingService,
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return [
            'transaction.created',
            'transaction.updated',
            'budget.updated',
            'budgetperiod.updated',
        ];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'BudgetThresholdHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        if ($data instanceof Transaction && $data->category_id !== null) {
            return $data->type === 'expense'
                || ($data->type === 'transfer' && $data->goal_id !== null);
        }
        if (is_array($data) && isset($data['budget']) && $data['budget'] instanceof Budget) {
            return true;
        }
        if (is_array($data) && isset($data['budgetPeriod']) && $data['budgetPeriod'] instanceof BudgetPeriod) {
            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        if ($data instanceof Transaction) {
            return $this->handleTransaction($data);
        }
        if (is_array($data) && isset($data['budget']) && $data['budget'] instanceof Budget) {
            return $this->handleBudget($data['budget']);
        }
        if (is_array($data) && isset($data['budgetPeriod']) && $data['budgetPeriod'] instanceof BudgetPeriod) {
            return $this->handleBudgetPeriod($data['budgetPeriod']);
        }

        return null;
    }

    /**
     * Handle threshold check triggered by transaction.
     *
     * @param  Transaction  $transaction
     * @return array|null
     */
    private function handleTransaction(Transaction $transaction): ?array
    {
        $user = $transaction->user;

        if (! $user) {
            return null;
        }

        $budget = Budget::where('user_id', $user->id)
            ->where('category_id', $transaction->category_id)
            ->first();

        if (! $budget) {
            return null;
        }

        $month = (int) $transaction->date->format('m');
        $year = (int) $transaction->date->format('Y');

        return $this->checkThreshold($budget, $user, $month, $year);
    }

    /**
     * Handle threshold check triggered by budget template update.
     *
     * @param  Budget  $budget
     * @return array|null
     */
    private function handleBudget(Budget $budget): ?array
    {
        $user = $budget->user;
        if (! $user) {
            return null;
        }

        $month = (int) now()->month;
        $year = (int) now()->year;

        return $this->checkThreshold($budget, $user, $month, $year);
    }

    /**
     * Handle threshold check triggered by budget period update.
     *
     * @param  BudgetPeriod  $budgetPeriod
     * @return array|null
     */
    private function handleBudgetPeriod(BudgetPeriod $budgetPeriod): ?array
    {
        $budget = $budgetPeriod->budget;
        if (! $budget) {
            return null;
        }

        $user = $budget->user;
        if (! $user) {
            return null;
        }

        $periodDate = $budgetPeriod->getPeriodDate();
        $month = (int) $periodDate->month;
        $year = (int) $periodDate->year;

        return $this->checkThreshold($budget, $user, $month, $year);
    }

    /**
     * Check if budget exceeds threshold and return signal data if so.
     *
     * @param  Budget  $budget
     * @param  mixed  $user
     * @param  int  $month
     * @param  int  $year
     * @return array|null
     */
    private function checkThreshold(Budget $budget, $user, int $month, int $year): ?array
    {
        $details = $this->reportingService->getCategoryBudgetDetails($budget, $month, $year);
        if (! $details) {
            return null;
        }

        $currency = $details['currency'] ?? settings('treasury_reference_currency', 'IDR');
        $percentage = $details['health'] ?? 0;
        $spent = $details['spent'] ?? 0;
        $totalLimit = $details['total_limit'] ?? 0;

        $warnThreshold = (float) settings('treasury_budget_warning_threshold', 80);
        $overThreshold = (float) settings('treasury_budget_over_threshold', 100);

        $budgetName = $budget->category?->name ?? 'Budget';
        $period = BudgetPeriod::formatPeriod($month, $year);
        $percentInt = (int) $percentage;
        $formattedSpent = $this->currencyFormatter->format($spent, $currency);
        $formattedLimit = $this->currencyFormatter->format($totalLimit, $currency);

        if ($percentage >= $overThreshold) {
            $cacheKey = "budget_signal_{$budget->id}_{$period}_overbudget_sent";
            if (Cache::has($cacheKey)) {
                return null;
            }

            Cache::put($cacheKey, true, now()->endOfMonth());

            Log::info('BudgetThresholdHandler: Overbudget', [
                'budget_id' => $budget->id,
                'percentage' => $percentage,
            ]);

            $isExceeded = $percentage > $overThreshold;

            if ($isExceeded) {
                return [
                    'type' => 'alert',
                    'title' => 'Budget Limit Exceeded',
                    'message' => "Your '{$budgetName}' budget has exceeded its limit. You've spent {$formattedSpent} of {$formattedLimit} ({$percentInt}%). Consider reducing spending in this category.",
                    'url' => route('treasury.budgets.index'),
                    'category' => 'treasury_budget',
                ];
            }

            return [
                'type' => 'warning',
                'title' => 'Budget Limit Reached',
                'message' => "Your '{$budgetName}' budget is fully used. You've spent {$formattedSpent} of {$formattedLimit} ({$percentInt}%). No more budget remaining for this period.",
                'url' => route('treasury.budgets.index'),
                'category' => 'treasury_budget',
            ];
        }

        if ($percentage >= $warnThreshold) {
            $cacheKey = "budget_signal_{$budget->id}_{$period}_warning_sent";
            if (Cache::has($cacheKey)) {
                return null;
            }

            Cache::put($cacheKey, true, now()->endOfMonth());

            Log::info('BudgetThresholdHandler: Warning threshold', [
                'budget_id' => $budget->id,
                'percentage' => $percentage,
            ]);

            return [
                'type' => 'warning',
                'title' => 'Budget Approaching Limit',
                'message' => "Your '{$budgetName}' budget is at {$percentInt}% capacity. You've spent {$formattedSpent} of {$formattedLimit}. Monitor your spending to stay on track.",
                'url' => route('treasury.budgets.index'),
                'category' => 'treasury_budget',
            ];
        }

        return null;
    }
}
