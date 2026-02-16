<?php

namespace Modules\Treasury\Services\Transaction;

use Carbon\Carbon;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

class TransactionStatsService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter,
        private readonly TransactionQueryService $queryService
    ) {}

    /**
     * Get current month's income and expense with proper currency conversion.
     */
    public function getCurrentMonthStats(User $user, int $month, int $year, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $startDate = Carbon::create($year, $month)->startOfMonth();
        $endDate = Carbon::create($year, $month)->endOfMonth();

        $query = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type', ['income', 'expense']);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'type', 'wallet_id']);

        return $this->sumWithConversion($transactions, $referenceCurrency);
    }

    /**
     * Get daily income vs expense summary.
     */
    public function getDailyStats(User $user, ?string $date = null, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $date = $date ?? now()->format('Y-m-d');

        $query = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereIn('type', ['income', 'expense'])
            ->whereDate('date', $date);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'type', 'wallet_id']);
        $totals = $this->sumWithConversion($transactions, $referenceCurrency);

        return [
            'income' => $totals['income'],
            'expense' => $totals['expense'],
            'balance' => $totals['income'] - $totals['expense'],
            'date' => $date,
        ];
    }

    /**
     * Get monthly income vs expense summary (last 6 months).
     */
    public function getMonthlySummary(User $user, int $month, int $year, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $data = [];
        $startDate = Carbon::create($year, $month)->subMonths(5)->startOfMonth();
        $endDate = Carbon::create($year, $month)->endOfMonth();

        $query = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type', ['income', 'expense']);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'type', 'wallet_id', 'date']);

        // Group by month and convert currencies
        $monthlyTotals = [];
        foreach ($transactions as $tx) {
            $monthKey = $tx->date->format('Y-m');
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );

            if (! isset($monthlyTotals[$monthKey])) {
                $monthlyTotals[$monthKey] = ['income' => 0.0, 'expense' => 0.0];
            }

            if ($tx->type === 'income') {
                $monthlyTotals[$monthKey]['income'] += $convertedAmount;
            } else {
                $monthlyTotals[$monthKey]['expense'] += $convertedAmount;
            }
        }

        // Map results back to the 6-month sequence
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::create($year, $month)->subMonths($i);
            $monthKey = $date->format('Y-m');

            $data[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'income' => $monthlyTotals[$monthKey]['income'] ?? 0.0,
                'expense' => $monthlyTotals[$monthKey]['expense'] ?? 0.0,
            ];
        }

        return $data;
    }

    /**
     * Get yearly income vs expense summary.
     */
    public function getYearlySummary(User $user, int $year, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $query = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereIn('type', ['income', 'expense'])
            ->whereYear('date', $year);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'type', 'wallet_id']);
        $totals = $this->sumWithConversion($transactions, $referenceCurrency);

        return [
            'income' => $totals['income'],
            'expense' => $totals['expense'],
            'balance' => $totals['income'] - $totals['expense'],
            'year' => $year,
        ];
    }

    /**
     * Sum transaction amounts with currency conversion by type.
     */
    private function sumWithConversion($transactions, string $referenceCurrency): array
    {
        $totalIncome = 0.0;
        $totalExpense = 0.0;

        foreach ($transactions as $tx) {
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );

            if ($tx->type === 'income') {
                $totalIncome += $convertedAmount;
            } elseif ($tx->type === 'expense') {
                $totalExpense += $convertedAmount;
            }
        }

        return ['income' => $totalIncome, 'expense' => $totalExpense];
    }

    /**
     * Calculate historical net worth by working backwards from current balance.
     *
     * For each month, we subtract that month's income and add expenses
     * to get what the net worth was at the END of the previous month.
     *
     * @return array Array of [month => 'Mon', netWorth => float]
     */
    public function getNetWorthHistory(User $user, float $currentNetWorth, int $months = 6, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $now = now();

        // Get monthly income/expense for the last N months
        $startDate = $now->copy()->subMonths($months - 1)->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        $query = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type', ['income', 'expense']);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'type', 'wallet_id', 'date']);

        // Group by month
        $monthlyTotals = [];
        foreach ($transactions as $tx) {
            $monthKey = $tx->date->format('Y-m');
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );

            if (! isset($monthlyTotals[$monthKey])) {
                $monthlyTotals[$monthKey] = ['income' => 0.0, 'expense' => 0.0];
            }

            if ($tx->type === 'income') {
                $monthlyTotals[$monthKey]['income'] += $convertedAmount;
            } else {
                $monthlyTotals[$monthKey]['expense'] += $convertedAmount;
            }
        }

        // Build the history array, starting from current and working backwards
        $data = [];
        $runningNetWorth = $currentNetWorth;

        // Generate months from most recent to oldest
        for ($i = 0; $i < $months; $i++) {
            $date = $now->copy()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->format('M');

            // Store current month's net worth
            $data[] = [
                'month' => $monthLabel,
                'year' => $date->format('Y'),
                'netWorth' => $runningNetWorth,
            ];

            // Calculate previous month's net worth by reversing transactions
            $income = $monthlyTotals[$monthKey]['income'] ?? 0.0;
            $expense = $monthlyTotals[$monthKey]['expense'] ?? 0.0;
            $runningNetWorth = $runningNetWorth - $income + $expense;
        }

        // Reverse so oldest is first
        return array_reverse($data);
    }
}
