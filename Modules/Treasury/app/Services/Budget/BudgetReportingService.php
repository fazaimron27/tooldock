<?php

/**
 * Budget Reporting Service
 *
 * Handles data aggregation for budget reports, summaries, and status tracking.
 * Utilizes BudgetRolloverService for accurate dynamic mathematical inputs.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Budget;

use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

/**
 * Budget Reporting Service
 *
 * Handles data aggregation for budget reports, summaries, and status tracking.
 * Utilizes BudgetRolloverService for accurate dynamic mathematical inputs.
 */
class BudgetReportingService
{
    public function __construct(
        private readonly BudgetRolloverService $rolloverService,
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /**
     * Get the monthly budget report comparing plan vs actual spending.
     *
     * @param  User  $user
     * @param  int  $month
     * @param  int  $year
     * @param  array  $filters
     * @return Collection
     */
    public function getMonthlyReport(User $user, int $month, int $year, array $filters = []): Collection
    {
        $period = BudgetPeriod::formatPeriod($month, $year);

        $allBudgets = Budget::where('user_id', $user->id)
            ->where(function ($query) use ($period) {
                $query->where('is_active', true)
                    ->orWhereHas('periods', function ($q) use ($period) {
                        $q->where('period', $period);
                    });
            })
            ->with(['category', 'periods' => function ($query) use ($period) {
                $query->where('period', $period);
            }])
            ->get();

        $budgetsWithRollover = $allBudgets->filter(fn ($b) => $b->rollover_enabled);
        if ($budgetsWithRollover->isNotEmpty()) {
            $this->rolloverService->preloadForBudgets($budgetsWithRollover, $month, $year, $filters);
        }

        $budgets = $allBudgets
            ->filter(function ($budget) use ($month, $year, $filters) {
                if ($budget->is_recurring) {
                    return true;
                }
                if ($budget->periods->isNotEmpty()) {
                    return true;
                }

                if ($budget->rollover_enabled) {
                    $rollover = $this->rolloverService->calculateRollover($budget, $month, $year, $filters);
                    if ($rollover != 0) {
                        return true;
                    }
                }

                $createdAt = $budget->created_at;

                return $createdAt->month === $month && $createdAt->year === $year;
            })
            ->values();

        $existingPeriods = BudgetPeriod::whereIn('budget_id', $budgets->pluck('id'))
            ->where('period', $period)
            ->get()
            ->keyBy('budget_id');

        $spendingQuery = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereNotNull('category_id');

        $this->applyFilters($spendingQuery, $filters);

        $transactions = $spendingQuery->get(['amount', 'wallet_id', 'category_id']);

        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $rawSpendingByCategory = [];
        foreach ($transactions as $tx) {
            $categoryId = $tx->category_id;
            if (! isset($rawSpendingByCategory[$categoryId])) {
                $rawSpendingByCategory[$categoryId] = [];
            }
            $rawSpendingByCategory[$categoryId][] = [
                'amount' => (float) $tx->amount,
                'currency' => $tx->wallet?->currency ?? $referenceCurrency,
            ];
        }

        $spendingByBudget = [];
        foreach ($budgets as $budget) {
            $categoryId = $budget->category_id;
            $budgetCurrency = $budget->currency ?? $referenceCurrency;
            $total = 0.0;

            if (isset($rawSpendingByCategory[$categoryId])) {
                foreach ($rawSpendingByCategory[$categoryId] as $txData) {
                    $total += $this->currencyConverter->convert(
                        $txData['amount'],
                        $txData['currency'],
                        $budgetCurrency
                    );
                }
            }

            $spendingByBudget[$budget->id] = $total;
        }

        return $budgets->map(function ($budget) use ($user, $existingPeriods, $spendingByBudget, $period, $month, $year, $filters) {
            $categoryId = $budget->category_id;
            $existingPeriod = $existingPeriods->get($budget->id);

            $amount = $existingPeriod ? (float) $existingPeriod->amount : (float) $budget->amount;
            $rollover = $budget->rollover_enabled ? $this->rolloverService->calculateRollover($budget, $month, $year, $filters) : 0;
            $spent = $spendingByBudget[$budget->id] ?? 0;
            $totalLimit = $amount + $rollover;

            $health = $totalLimit > 0 ? round(($spent / $totalLimit) * 100, 1) : ($spent > 0 ? 100 : 0);

            return [
                'id' => $budget->id,
                'period_id' => $existingPeriod?->id,
                'period' => $period,
                'category' => $budget->category?->name ?? 'Uncategorized',
                'category_id' => $categoryId,
                'category_color' => $budget->category?->color,
                'currency' => $budget->currency,
                'limit' => $amount,
                'rollover' => $rollover,
                'total_limit' => $totalLimit,
                'spent' => (float) $spent,
                'remaining' => max(0, $totalLimit - $spent),
                'health' => min($health, 100),
                'raw_health' => $health,
                'status' => $this->getStatus($health, $user),
                'is_recurring' => $budget->is_recurring,
                'rollover_enabled' => $budget->rollover_enabled,
                'description' => $existingPeriod?->description,
                'has_period' => $existingPeriod !== null,
            ];
        });
    }

    /**
     * Get detailed budget information for a specific category in a given month.
     * All values are returned in the budget's native currency.
     *
     * @param  Budget  $budget
     * @param  int  $month
     * @param  int  $year
     * @param  array  $filters
     * @return array|null
     */
    public function getCategoryBudgetDetails(Budget $budget, int $month, int $year, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $budgetCurrency = $budget->currency ?? $referenceCurrency;
        $period = BudgetPeriod::formatPeriod($month, $year);
        $savedPeriod = BudgetPeriod::where('budget_id', $budget->id)->where('period', $period)->first();

        $limit = $savedPeriod ? (float) $savedPeriod->amount : (float) $budget->amount;
        $rollover = $budget->rollover_enabled ? $this->rolloverService->calculateRollover($budget, $month, $year, $filters) : 0;

        $spentQuery = Transaction::where('user_id', $budget->user_id)
            ->with('wallet:id,currency')
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->where('category_id', $budget->category_id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        $this->applyFilters($spentQuery, $filters);

        $spent = 0.0;
        foreach ($spentQuery->get(['amount', 'wallet_id']) as $tx) {
            $walletCurrency = $tx->wallet?->currency ?? $budgetCurrency;
            $spent += $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $budgetCurrency
            );
        }

        $totalLimit = $limit + $rollover;

        return [
            'currency' => $budgetCurrency,
            'limit' => $limit,
            'rollover' => $rollover,
            'total_limit' => $totalLimit,
            'spent' => $spent,
            'remaining' => max(0, $totalLimit - $spent),
            'health' => $totalLimit > 0 ? ($spent / $totalLimit) * 100 : ($spent > 0 ? 100 : 0),
        ];
    }

    /**
     * Get summary statistics, including unbudgeted expenses.
     * All aggregate values are returned in the user's reference currency.
     *
     * @param  User  $user
     * @param  int  $month
     * @param  int  $year
     * @param  array  $filters
     * @return array
     */
    public function getMonthlySummary(User $user, int $month, int $year, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $report = $this->getMonthlyReport($user, $month, $year, $filters);

        $totalExpenseQuery = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        $this->applyFilters($totalExpenseQuery, $filters);

        $realTotalSpent = 0.0;
        foreach ($totalExpenseQuery->get(['amount', 'wallet_id']) as $tx) {
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $realTotalSpent += $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );
        }

        $totalBudgeted = 0.0;
        $totalBudgetedSpent = 0.0;
        $totalRemaining = 0.0;
        $totalRollover = 0.0;

        foreach ($report as $budgetData) {
            $budgetCurrency = $budgetData['currency'] ?? $referenceCurrency;

            $totalBudgeted += $this->currencyConverter->convert(
                (float) $budgetData['total_limit'],
                $budgetCurrency,
                $referenceCurrency
            );
            $totalBudgetedSpent += $this->currencyConverter->convert(
                (float) $budgetData['spent'],
                $budgetCurrency,
                $referenceCurrency
            );
            $totalRemaining += $this->currencyConverter->convert(
                (float) $budgetData['remaining'],
                $budgetCurrency,
                $referenceCurrency
            );
            $totalRollover += $this->currencyConverter->convert(
                (float) $budgetData['rollover'],
                $budgetCurrency,
                $referenceCurrency
            );
        }

        return [
            'total_budgeted' => $totalBudgeted,
            'total_spent' => $realTotalSpent,
            'total_budgeted_spent' => $totalBudgetedSpent,
            'total_unbudgeted_spent' => max(0, $realTotalSpent - $totalBudgetedSpent),
            'total_remaining' => $totalRemaining,
            'total_rollover' => $totalRollover,
            'categories_count' => $report->count(),
            'overbudget_count' => $report->filter(fn (array $b) => $b['status'] === 'overbudget')->count(),
            'warning_count' => $report->filter(fn (array $b) => $b['status'] === 'warning')->count(),
            'safe_count' => $report->filter(fn (array $b) => $b['status'] === 'safe')->count(),
        ];
    }

    /**
     * Determine health status.
     *
     * @param  float  $health
     * @param  User|null  $user
     * @return string
     */
    private function getStatus(float $health, ?User $user = null): string
    {
        $overThreshold = (float) settings('treasury_budget_overbudget_threshold', 100);
        $warnThreshold = (float) settings('treasury_budget_warning_threshold', 80);

        if ($health > $overThreshold) {
            return 'overbudget';
        }
        if ($health >= $warnThreshold) {
            return 'warning';
        }

        return 'safe';
    }

    /**
     * Apply query filters for wallet and category.
     *
     * @param  mixed  $query
     * @param  array  $filters
     * @return mixed
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        $model = $query->getModel();
        if ($model instanceof Transaction) {
            if (! empty($filters['date_from'])) {
                $query->where('date', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->where('date', '<=', $filters['date_to']);
            }
        }
    }
}
