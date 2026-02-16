<?php

/**
 * Treasury Overview Service
 *
 * Provides aggregated financial data for the Treasury module landing page
 * including net worth, wallets, goals, budgets, and health metrics.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\User;
use Modules\Treasury\Services\Budget\BudgetReportingService;
use Modules\Treasury\Services\Exchange\CurrencyConverter;
use Modules\Treasury\Services\Goal\GoalProgressService;
use Modules\Treasury\Services\Transaction\TransactionQueryService;
use Modules\Treasury\Services\Transaction\TransactionStatsService;
use Modules\Treasury\Services\Wallet\WalletSummaryService;

/**
 * Service for aggregating Treasury module overview data.
 */
class TreasuryOverviewService
{
    public function __construct(
        private readonly BudgetReportingService $budgetService,
        private readonly CurrencyConverter $currencyConverter,
        private readonly WalletSummaryService $walletService,
        private readonly GoalProgressService $goalService,
        private readonly TransactionQueryService $transactionQuery,
        private readonly TransactionStatsService $transactionStats
    ) {}

    /**
     * Get all overview data for the Treasury module landing page.
     *
     * @param  User|null  $user
     * @param  array  $filters
     * @return array
     */
    public function getOverviewData(?User $user = null, array $filters = []): array
    {
        $user = $user ?? Auth::user();
        $now = now();
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $budgetReport = $this->budgetService->getMonthlyReport($user, $now->month, $now->year, $filters);

        $budgets = $budgetReport->map(function (array $b) use ($referenceCurrency) {
            $budgetCurrency = $b['currency'] ?? $referenceCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $b['limit'],
                $budgetCurrency,
                $referenceCurrency
            );
            $convertedSpent = $this->currencyConverter->convert(
                (float) $b['spent'],
                $budgetCurrency,
                $referenceCurrency
            );

            return [
                'id' => $b['id'],
                'name' => $b['category'],
                'amount' => $b['limit'],
                'spent' => $b['spent'],
                'currency' => $budgetCurrency,
                'converted_amount' => $convertedAmount,
                'converted_spent' => $convertedSpent,
                'category' => [
                    'name' => $b['category'],
                    'color' => $b['category_color'],
                ],
            ];
        })->values()->toArray();

        return [
            'netWorth' => $this->walletService->getNetWorth($user, $filters),
            'wallets' => $this->walletService->getWallets($user, $filters),
            'goals' => $this->goalService->getGoals($user, $filters),
            'budgetSummary' => $this->budgetService->getMonthlySummary($user, $now->month, $now->year, $filters),
            'budgets' => $budgets,
            'recentTransactions' => $this->transactionQuery->getRecent($user, 10, $filters),
            'monthlySummary' => $this->transactionStats->getCurrentMonthStats($user, $now->month, $now->year, $filters),
            'monthlyTrend' => $this->transactionStats->getMonthlySummary($user, $now->month, $now->year, $filters),
            'financialHealth' => $this->getFinancialHealthData($user, $now, $filters),
            'referenceCurrency' => $referenceCurrency,
        ];
    }

    /**
     * Calculate financial health metrics for the health summary dialog.
     *
     * @param  User  $user
     * @param  mixed  $now
     * @param  array  $filters
     * @return array
     */
    private function getFinancialHealthData(User $user, $now, array $filters = []): array
    {
        $currentMonth = $this->transactionStats->getCurrentMonthStats($user, $now->month, $now->year, $filters);
        $lastMonth = $this->transactionStats->getCurrentMonthStats(
            $user,
            $now->copy()->subMonth()->month,
            $now->copy()->subMonth()->year,
            $filters
        );
        $netWorth = $this->walletService->getNetWorth($user, $filters);
        $budgetSummary = $this->budgetService->getMonthlySummary($user, $now->month, $now->year, $filters);
        $goals = $this->goalService->getGoals($user, $filters);

        $monthlyIncome = $currentMonth['income'] ?? 0;
        $monthlyExpense = $currentMonth['expense'] ?? 0;
        $savingsRate = $monthlyIncome > 0 ? (($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100 : 0;

        $budgetUtilization = ($budgetSummary['total_budgeted'] ?? 0) > 0
            ? (($budgetSummary['total_spent'] ?? 0) / ($budgetSummary['total_budgeted'] ?? 1)) * 100
            : 0;

        $totalGoalTarget = collect($goals)->sum('target_amount');
        $totalGoalProgress = collect($goals)->sum('current_amount');
        $goalProgress = $totalGoalTarget > 0 ? ($totalGoalProgress / $totalGoalTarget) * 100 : 0;

        $lastMonthIncome = $lastMonth['income'] ?? 0;
        $lastMonthExpense = $lastMonth['expense'] ?? 0;

        return [
            'netWorth' => $netWorth['total'] ?? 0,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'savingsRate' => round($savingsRate, 1),
            'budgetUtilization' => round($budgetUtilization, 1),
            'goalProgress' => round($goalProgress, 1),
            'incomeVsLastMonth' => $lastMonthIncome > 0
                ? round((($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100, 1)
                : 0,
            'expenseVsLastMonth' => $lastMonthExpense > 0
                ? round((($monthlyExpense - $lastMonthExpense) / $lastMonthExpense) * 100, 1)
                : 0,
        ];
    }
}
