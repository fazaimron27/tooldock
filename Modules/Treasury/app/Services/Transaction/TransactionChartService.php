<?php

/**
 * Transaction Chart Service
 *
 * Provides time-series chart data for transaction visualization
 * at hourly, daily, and monthly granularity.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Transaction;

use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

/**
 * Service for generating transaction chart data at various time granularities.
 */
class TransactionChartService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter,
        private readonly TransactionQueryService $queryService
    ) {}

    /**
     * Get hourly trend data for today.
     *
     * @param  User  $user
     * @param  string|null  $date
     * @param  array  $filters
     * @return array
     */
    public function getHourlyChartData(User $user, ?string $date = null, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $date = $date ?? now()->format('Y-m-d');
        $data = [];

        $query = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereIn('type', ['income', 'expense'])
            ->whereDate('date', $date);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'type', 'wallet_id', 'created_at']);

        $hourlyTotals = [];
        foreach ($transactions as $tx) {
            $hour = (int) $tx->created_at->format('G');
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );

            if (! isset($hourlyTotals[$hour])) {
                $hourlyTotals[$hour] = ['income' => 0.0, 'expense' => 0.0];
            }

            if ($tx->type === 'income') {
                $hourlyTotals[$hour]['income'] += $convertedAmount;
            } else {
                $hourlyTotals[$hour]['expense'] += $convertedAmount;
            }
        }

        for ($h = 0; $h < 24; $h++) {
            $data[] = [
                'name' => sprintf('%02d:00', $h),
                'income' => $hourlyTotals[$h]['income'] ?? 0.0,
                'expense' => $hourlyTotals[$h]['expense'] ?? 0.0,
            ];
        }

        return $data;
    }

    /**
     * Get daily trend data for a specific month.
     *
     * @param  User  $user
     * @param  int  $month
     * @param  int  $year
     * @param  array  $filters
     * @return array
     */
    public function getDailyChartData(User $user, int $month, int $year, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $data = [];

        $query = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereIn('type', ['income', 'expense'])
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'type', 'wallet_id', 'date']);

        $dailyTotals = [];
        foreach ($transactions as $tx) {
            $dateKey = $tx->date->format('Y-m-d');
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );

            if (! isset($dailyTotals[$dateKey])) {
                $dailyTotals[$dateKey] = ['income' => 0.0, 'expense' => 0.0];
            }

            if ($tx->type === 'income') {
                $dailyTotals[$dateKey]['income'] += $convertedAmount;
            } else {
                $dailyTotals[$dateKey]['expense'] += $convertedAmount;
            }
        }

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $i);

            $data[] = [
                'name' => (string) $i,
                'date' => $currentDate,
                'income' => $dailyTotals[$currentDate]['income'] ?? 0.0,
                'expense' => $dailyTotals[$currentDate]['expense'] ?? 0.0,
            ];
        }

        return $data;
    }

    /**
     * Get monthly trend data for a specific year.
     *
     * @param  User  $user
     * @param  int  $year
     * @param  array  $filters
     * @return array
     */
    public function getMonthlyChartData(User $user, int $year, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $data = [];

        $query = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereIn('type', ['income', 'expense'])
            ->whereYear('date', $year);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'type', 'wallet_id', 'date']);

        $monthlyTotals = [];
        foreach ($transactions as $tx) {
            $monthNum = (int) $tx->date->format('n');
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );

            if (! isset($monthlyTotals[$monthNum])) {
                $monthlyTotals[$monthNum] = ['income' => 0.0, 'expense' => 0.0];
            }

            if ($tx->type === 'income') {
                $monthlyTotals[$monthNum]['income'] += $convertedAmount;
            } else {
                $monthlyTotals[$monthNum]['expense'] += $convertedAmount;
            }
        }

        for ($m = 1; $m <= 12; $m++) {
            $data[] = [
                'name' => date('M', mktime(0, 0, 0, $m, 1)),
                'income' => $monthlyTotals[$m]['income'] ?? 0.0,
                'expense' => $monthlyTotals[$m]['expense'] ?? 0.0,
            ];
        }

        return $data;
    }
}
