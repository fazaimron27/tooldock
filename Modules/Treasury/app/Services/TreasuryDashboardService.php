<?php

/**
 * Treasury Dashboard Service
 *
 * Registers dashboard widgets for the Treasury module, providing
 * financial overview data including stats, charts, and activity feeds.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Auth;
use Modules\Treasury\Services\Budget\BudgetReportingService;
use Modules\Treasury\Services\Goal\GoalProgressService;
use Modules\Treasury\Services\Support\CurrencyFormatter;
use Modules\Treasury\Services\Transaction\TransactionCategoryService;
use Modules\Treasury\Services\Transaction\TransactionChartService;
use Modules\Treasury\Services\Transaction\TransactionQueryService;
use Modules\Treasury\Services\Transaction\TransactionStatsService;
use Modules\Treasury\Services\Wallet\WalletSummaryService;

/**
 * Service for registering Treasury dashboard widgets.
 */
class TreasuryDashboardService
{
    public function __construct(
        private readonly WalletSummaryService $walletService,
        private readonly GoalProgressService $goalService,
        private readonly TransactionQueryService $transactionQuery,
        private readonly TransactionStatsService $transactionStats,
        private readonly TransactionChartService $transactionChart,
        private readonly TransactionCategoryService $transactionCategory,
        private readonly BudgetReportingService $budgetService,
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /**
     * Register all dashboard widgets for the Treasury module.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'Financial Overview',
            'Monitor your income, expenses, and transaction history across all wallets.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Net Worth',
                value: fn ($params) => $this->walletService->getNetWorth(Auth::user(), $params)['total'],
                icon: 'Wallet',
                module: $moduleName,
                group: 'Financial Health',
                order: 1,
                description: 'Total balance across all active wallets',
                config: ['valueType' => 'currency'],
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'table',
                title: 'Active Wallets',
                value: 0,
                icon: 'CreditCard',
                module: $moduleName,
                group: 'Financial Health',
                order: 2,
                description: 'Current balances in your digital and physical wallets',
                data: fn ($params) => $this->walletService->getWallets(Auth::user(), $params),
                config: [
                    'columns' => [
                        ['key' => 'name', 'label' => 'Wallet'],
                        ['key' => 'balance', 'label' => 'Balance', 'type' => 'currency'],
                    ],
                ],
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Cash In Today',
                value: fn ($params) => $this->transactionStats->getDailyStats(Auth::user(), null, $params)['income'],
                icon: 'TrendingUp',
                module: $moduleName,
                group: 'Today',
                order: 10,
                description: 'Total income received today across all wallets',
                config: ['timeframe' => 'Today', 'valueType' => 'currency'],
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Cash Out Today',
                value: fn ($params) => $this->transactionStats->getDailyStats(Auth::user(), null, $params)['expense'],
                icon: 'TrendingDown',
                module: $moduleName,
                group: 'Today',
                order: 11,
                description: 'Total expenses made today across all wallets',
                config: ['timeframe' => 'Today', 'valueType' => 'currency'],
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'This Month\'s Income',
                value: fn ($params) => $this->transactionStats->getMonthlySummary(Auth::user(), now()->month, now()->year, $params)[5]['income'] ?? 0,
                icon: 'Coins',
                module: $moduleName,
                group: 'This Month',
                order: 20,
                description: 'Total earnings received during the current month',
                config: ['timeframe' => 'This Month', 'valueType' => 'currency'],
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'This Month\'s Spending',
                value: fn ($params) => $this->transactionStats->getMonthlySummary(Auth::user(), now()->month, now()->year, $params)[5]['expense'] ?? 0,
                icon: 'ShoppingBag',
                module: $moduleName,
                group: 'This Month',
                order: 21,
                description: 'All expenses recorded during the current month',
                config: ['timeframe' => 'This Month', 'valueType' => 'currency'],
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Annual Income',
                value: fn ($params) => $this->transactionStats->getYearlySummary(Auth::user(), now()->year, $params)['income'],
                icon: 'Landmark',
                module: $moduleName,
                group: 'This Year',
                order: 30,
                description: 'Cumulative earnings for the current year',
                config: ['timeframe' => 'This Year', 'valueType' => 'currency'],
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Annual Spending',
                value: fn ($params) => $this->transactionStats->getYearlySummary(Auth::user(), now()->year, $params)['expense'],
                icon: 'Receipt',
                module: $moduleName,
                group: 'This Year',
                order: 31,
                description: 'Cumulative spending for the current year',
                config: ['timeframe' => 'This Year', 'valueType' => 'currency'],
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'Today\'s Cash Flow',
                value: 0,
                icon: 'Zap',
                module: $moduleName,
                group: 'Today',
                chartType: 'bar',
                description: 'Real-time income and expense distribution for today',
                data: fn ($params) => $this->transactionChart->getHourlyChartData(Auth::user(), null, $params),
                xAxisKey: 'name',
                dataKeys: ['income', 'expense'],
                config: [
                    'income' => ['label' => 'Income', 'color' => '#10b981'],
                    'expense' => ['label' => 'Expense', 'color' => '#f43f5e'],
                    'valueType' => 'currency',
                ],
                order: 12,
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'Daily Cash Flow Trend',
                value: 0,
                icon: 'LineChart',
                module: $moduleName,
                group: 'This Month',
                chartType: 'area',
                description: 'Visualization of daily income and expenses for the current month',
                data: fn ($params) => $this->transactionChart->getDailyChartData(Auth::user(), now()->month, now()->year, $params),
                xAxisKey: 'name',
                dataKeys: ['income', 'expense'],
                config: [
                    'income' => ['label' => 'Income', 'color' => '#10b981'],
                    'expense' => ['label' => 'Expense', 'color' => '#f43f5e'],
                    'valueType' => 'currency',
                ],
                order: 22,
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'Annual Cash Flow Performance',
                value: 0,
                icon: 'BarChart',
                module: $moduleName,
                group: 'This Year',
                chartType: 'bar',
                description: 'Comparison of income and expenses month-by-month for the year',
                data: fn ($params) => $this->transactionChart->getMonthlyChartData(Auth::user(), now()->year, $params),
                xAxisKey: 'name',
                dataKeys: ['income', 'expense'],
                config: [
                    'income' => ['label' => 'Income', 'color' => '#10b981'],
                    'expense' => ['label' => 'Expense', 'color' => '#ef4444'],
                    'valueType' => 'currency',
                ],
                order: 32,
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Latest Wallet Activity',
                value: 0,
                icon: 'History',
                module: $moduleName,
                description: 'A quick look at your most recent financial movements',
                data: fn ($params) => $this->transactionQuery->getRecent(Auth::user(), 5, $params),
                order: 50,
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'table',
                title: 'Category Spending Analysis',
                value: 0,
                icon: 'PieChart',
                module: $moduleName,
                description: 'Analyze which categories consumed the most budget this month',
                data: fn ($params) => $this->transactionCategory->getTopExpenseCategories(Auth::user(), now()->month, now()->year, 50, $params),
                config: [
                    'columns' => [
                        ['key' => 'category', 'label' => 'Category'],
                        ['key' => 'amount', 'label' => 'Amount', 'type' => 'currency'],
                    ],
                ],
                order: 60,
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'system',
                title: 'Savings & Goals Progress',
                value: 0,
                icon: 'Target',
                module: $moduleName,
                group: 'Savings Goals',
                order: 40,
                description: 'Track your progress towards financial freedom',
                data: fn ($params) => array_map(fn ($goal) => [
                    'label' => $goal['name'],
                    'value' => $goal['progress'].'%',
                    'percentage' => $goal['progress'],
                    'color' => $goal['category']['color'] ?? 'primary',
                ], $this->goalService->getGoals(Auth::user(), $params)),
                scope: 'both'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'system',
                title: 'Monthly Budget Performance',
                value: 0,
                icon: 'PieChart',
                module: $moduleName,
                group: 'Budget Tracking',
                order: 25,
                description: 'Current spending vs budget limit for active categories',
                data: fn ($params) => (function () use ($params) {
                    $budgetReport = $this->budgetService
                        ->getMonthlyReport(Auth::user(), now()->month, now()->year, $params);

                    if ($budgetReport->isEmpty()) {
                        return [];
                    }

                    $currency = settings('treasury_reference_currency', 'IDR');

                    return $budgetReport->map(function (array $budget) use ($currency) {
                        $spent = $this->currencyFormatter->format($budget['spent'], $currency);
                        $limit = $this->currencyFormatter->format($budget['total_limit'], $currency);

                        return [
                            'label' => $budget['category'],
                            'value' => "{$spent} / {$limit}",
                            'percentage' => min(100, $budget['total_limit'] > 0 ? round(($budget['spent'] / $budget['total_limit']) * 100, 1) : 0),
                            'color' => $budget['category_color'] ?? 'primary',
                        ];
                    })->toArray();
                })(),
                scope: 'both'
            )
        );
    }
}
