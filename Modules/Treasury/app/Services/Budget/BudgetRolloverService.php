<?php

/**
 * Budget Rollover Service
 *
 * Handles the recursive calculation of budget rollovers (debt/surplus).
 * Supports strict/envelope budgeting where overspending impacts future months.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Budget;

use Carbon\Carbon;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

/**
 * Budget Rollover Service
 *
 * Handles the recursive calculation of budget rollovers (debt/surplus).
 * Supports strict/envelope budgeting where overspending impacts future months.
 */
class BudgetRolloverService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /**
     * Runtime cache for rollover calculations to prevent redundant recursive calls.
     */
    private array $rolloverCache = [];

    /**
     * Runtime cache for spending aggregations used during rollover calculation.
     */
    private array $spendingCache = [];

    /**
     * Runtime cache for budget periods used during rollover calculation.
     */
    private array $periodCache = [];

    /**
     * Calculate rollover recursively from the budget template.
     * Supports negative rollovers (strict budgeting).
     *
     * @param  Budget  $budget  The budget template
     * @param  int  $month  Current month
     * @param  int  $year  Current year
     * @param  array  $filters  Optional filters
     * @param  int  $depth  Recursion depth to prevent infinite loops (max 12 months)
     * @return float
     */
    public function calculateRollover(Budget $budget, int $month, int $year, array $filters = [], int $depth = 0): float
    {
        $cacheKey = "{$budget->id}-{$month}-{$year}-".md5(serialize($filters));

        if (isset($this->rolloverCache[$cacheKey])) {
            return $this->rolloverCache[$cacheKey];
        }

        $targetDate = Carbon::create($year, $month)->startOfMonth();
        $createdAt = $budget->created_at->startOfMonth();

        if ($depth >= 12 || $targetDate->lessThanOrEqualTo($createdAt)) {
            return $this->rolloverCache[$cacheKey] = 0;
        }

        $previousDate = Carbon::create($year, $month)->subMonth();
        $previousPeriod = BudgetPeriod::formatPeriod($previousDate->month, $previousDate->year);

        $inheritedRollover = $this->calculateRollover(
            $budget,
            $previousDate->month,
            $previousDate->year,
            $filters,
            $depth + 1
        );

        $periodCacheKey = "{$budget->id}-{$previousPeriod}";
        $savedPeriod = $this->periodCache[$periodCacheKey] ?? BudgetPeriod::where('budget_id', $budget->id)
            ->where('period', $previousPeriod)
            ->first();

        $previousAmount = $savedPeriod ? (float) $savedPeriod->amount : (float) $budget->amount;
        $previousTotalLimit = $previousAmount + $inheritedRollover;

        $budgetCurrency = $budget->currency ?? settings('treasury_reference_currency', 'IDR');
        $spent = $this->getSpendingForRollover(
            $budget->user_id,
            $budget->category_id,
            $previousDate->month,
            $previousDate->year,
            $budgetCurrency,
            $filters
        );

        return $this->rolloverCache[$cacheKey] = $previousTotalLimit - $spent;
    }

    /**
     * Get spending for a specific category in a month, optimized for rollover calculations.
     * Converts all amounts to the target currency for accurate multi-currency aggregation.
     *
     * @param  string  $userId  The user ID
     * @param  string  $categoryId  The category ID
     * @param  int  $month  The month
     * @param  int  $year  The year
     * @param  string  $targetCurrency  Currency to convert spending totals to
     * @param  array  $filters  Optional filters
     * @return float
     */
    private function getSpendingForRollover(string $userId, string $categoryId, int $month, int $year, string $targetCurrency, array $filters = []): float
    {
        $cacheKey = "{$userId}-{$categoryId}-{$month}-{$year}-{$targetCurrency}-".md5(serialize($filters));

        if (isset($this->spendingCache[$cacheKey])) {
            return $this->spendingCache[$cacheKey];
        }

        $query = Transaction::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->where('category_id', $categoryId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('wallet:id,currency');

        if (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        $transactions = $query->get(['amount', 'wallet_id']);
        $total = 0.0;

        foreach ($transactions as $tx) {
            $walletCurrency = $tx->wallet?->currency ?? $targetCurrency;
            $total += $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $targetCurrency
            );
        }

        return $this->spendingCache[$cacheKey] = $total;
    }

    /**
     * Preload spending and period data for multiple budgets to avoid N+1 queries.
     * Call this before processing a batch of budgets with rollover enabled.
     *
     * @param  \Illuminate\Support\Collection  $budgets  Collection of Budget models
     * @param  int  $month  Target month
     * @param  int  $year  Target year
     * @param  array  $filters  Optional filters
     * @param  int  $monthsBack  How many months of history to preload
     * @return void
     */
    public function preloadForBudgets($budgets, int $month, int $year, array $filters = [], int $monthsBack = 12): void
    {
        $budgetIds = $budgets->pluck('id')->toArray();
        $categoryIds = $budgets->pluck('category_id')->toArray();
        $userIds = $budgets->pluck('user_id')->unique()->toArray();

        if (empty($budgetIds)) {
            return;
        }

        $periods = [];
        for ($i = 0; $i < $monthsBack; $i++) {
            $date = \Carbon\Carbon::create($year, $month)->subMonths($i);
            $periods[] = BudgetPeriod::formatPeriod($date->month, $date->year);
        }

        $allPeriods = BudgetPeriod::whereIn('budget_id', $budgetIds)
            ->whereIn('period', $periods)
            ->get();

        foreach ($allPeriods as $period) {
            $cacheKey = "{$period->budget_id}-{$period->period}";
            $this->periodCache[$cacheKey] = $period;
        }

        $startDate = \Carbon\Carbon::create($year, $month)->subMonths($monthsBack - 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month)->endOfMonth();

        $spendingQuery = Transaction::whereIn('user_id', $userIds)
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->whereIn('category_id', $categoryIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('wallet:id,currency');

        if (! empty($filters['wallet_id'])) {
            $spendingQuery->where('wallet_id', $filters['wallet_id']);
        }

        $allTransactions = $spendingQuery->get(['user_id', 'category_id', 'wallet_id', 'amount', 'date']);

        $categoryToBudgetCurrency = [];
        foreach ($budgets as $budget) {
            $budgetCurrency = $budget->currency ?? settings('treasury_reference_currency', 'IDR');
            $categoryToBudgetCurrency[$budget->user_id][$budget->category_id] = $budgetCurrency;
        }

        $grouped = [];
        foreach ($allTransactions as $tx) {
            $budgetCurrency = $categoryToBudgetCurrency[$tx->user_id][$tx->category_id]
                ?? settings('treasury_reference_currency', 'IDR');
            $walletCurrency = $tx->wallet?->currency ?? $budgetCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $budgetCurrency
            );

            $monthVal = $tx->date->month;
            $yearVal = $tx->date->year;
            $groupKey = "{$tx->user_id}-{$tx->category_id}-{$monthVal}-{$yearVal}-{$budgetCurrency}-".md5(serialize($filters));

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = 0.0;
            }
            $grouped[$groupKey] += $convertedAmount;
        }

        foreach ($grouped as $cacheKey => $total) {
            $this->spendingCache[$cacheKey] = $total;
        }
    }

    /**
     * Clear the runtime caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->rolloverCache = [];
        $this->spendingCache = [];
        $this->periodCache = [];
    }
}
