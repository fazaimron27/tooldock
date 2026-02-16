<?php

/**
 * Treasury Report Controller
 *
 * Generates financial reports including transaction ledgers, budget vs actual
 * comparisons, category breakdowns, per-wallet summaries, savings goal
 * progress with projections, and period-based financial summaries. Supports
 * CSV export of transaction data.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Categories\Models\Category;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class TreasuryReportController
 *
 * Provides multi-type financial reporting with filtering, aggregation, and export.
 */
class TreasuryReportController extends Controller
{
    /**
     * Display the reports page with all report types.
     *
     * @param  Request  $request  The incoming request with report type and filters
     * @return Response
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $reportType = $request->input('report_type', 'transaction');

        $startDate = $request->input('start_date') ?? now()->startOfMonth()->toDateString();
        $endDate = $request->input('end_date') ?? now()->endOfMonth()->toDateString();

        $wallets = Wallet::forUser()->active()->get(['id', 'name', 'currency', 'balance']);
        $categories = Category::where('type', 'transaction_category')->orderBy('name')->get(['id', 'name', 'color', 'parent_id']);

        $baseData = [
            'reportType' => $reportType,
            'wallets' => $wallets,
            'categories' => $categories,
            'types' => Transaction::TYPES,
            'filters' => [
                'report_type' => $reportType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'wallet_id' => $request->wallet_id,
                'type' => $request->type,
                'category_id' => $request->category_id,
            ],
            'referenceCurrency' => settings('treasury_reference_currency', 'IDR'),
        ];

        $reportData = match ($reportType) {
            'transaction' => $this->getTransactionReportData($request, $startDate, $endDate),
            'budget' => $this->getBudgetReportData($request, $startDate, $endDate),
            'category' => $this->getCategoryReportData($request, $startDate, $endDate),
            'wallet' => $this->getWalletReportData($request, $startDate, $endDate),
            'goal' => $this->getGoalReportData($request),
            'summary' => $this->getSummaryReportData($request, $startDate, $endDate),
            default => $this->getTransactionReportData($request, $startDate, $endDate),
        };

        return Inertia::render('Modules::Treasury/Reports/Index', array_merge($baseData, $reportData));
    }

    /**
     * Transaction Report - Transaction ledger with export.
     *
     * @param  Request  $request  The incoming request with optional wallet/type/category filters
     * @param  string  $startDate  The start date of the report period
     * @param  string  $endDate  The end date of the report period
     * @return array<string, mixed>
     */
    private function getTransactionReportData(Request $request, string $startDate, string $endDate): array
    {
        $query = Transaction::forUser()
            ->with(['wallet', 'destinationWallet', 'category'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('wallet_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('wallet_id', $request->wallet_id)
                    ->orWhere('destination_wallet_id', $request->wallet_id);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $transactions = $query->get();

        $summary = [
            'income' => $transactions->where('type', 'income')->sum('amount'),
            'expense' => $transactions->where('type', 'expense')->sum('amount'),
            'transfer' => $transactions->where('type', 'transfer')->sum('amount'),
            'count' => $transactions->count(),
        ];
        $summary['net'] = $summary['income'] - $summary['expense'];

        return [
            'transactions' => $transactions,
            'summary' => $summary,
        ];
    }

    /**
     * Budget Report - Budget vs actual spending comparison.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $startDate  The start date of the report period
     * @param  string  $endDate  The end date of the report period
     * @return array<string, mixed>
     */
    private function getBudgetReportData(Request $request, string $startDate, string $endDate): array
    {
        $period = Carbon::parse($startDate)->format('Y-m');

        $budgets = Budget::forUser()
            ->with(['category', 'periods' => function ($query) use ($period) {
                $query->where('period', $period);
            }])
            ->active()
            ->get();

        $spending = Transaction::forUser()
            ->where(function ($q) {
                $q->where('type', 'expense')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('category_id')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'category_id');

        $budgetData = $budgets->map(function ($budget) use ($spending) {
            $periodData = $budget->periods->first();
            $budgetAmount = $periodData?->amount ?? $budget->amount;
            $spent = $spending[$budget->category_id] ?? 0;
            $remaining = $budgetAmount - $spent;
            $percentage = $budgetAmount > 0 ? min(100, round(($spent / $budgetAmount) * 100, 1)) : 0;

            return [
                'id' => $budget->id,
                'category' => $budget->category,
                'budgeted' => $budgetAmount,
                'spent' => $spent,
                'remaining' => $remaining,
                'percentage' => $percentage,
                'currency' => $budget->currency,
                'is_over' => $spent > $budgetAmount,
                'rollover_enabled' => $budget->rollover_enabled,
            ];
        });

        $totals = [
            'budgeted' => $budgetData->sum('budgeted'),
            'spent' => $budgetData->sum('spent'),
            'remaining' => $budgetData->sum('remaining'),
        ];

        return [
            'budgets' => $budgetData,
            'budgetTotals' => $totals,
            'period' => $period,
        ];
    }

    /**
     * Category Report - Income/expense breakdown by category.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $startDate  The start date of the report period
     * @param  string  $endDate  The end date of the report period
     * @return array<string, mixed>
     */
    private function getCategoryReportData(Request $request, string $startDate, string $endDate): array
    {
        $transactions = Transaction::forUser()
            ->with('category')
            ->where(function ($q) {
                $q->whereIn('type', ['income', 'expense'])
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $categoryBreakdown = $transactions->groupBy('category_id')->map(function ($txs, $categoryId) {
            $category = $txs->first()->category;
            $income = $txs->where('type', 'income')->sum('amount');
            $expense = $txs->where('type', 'expense')->sum('amount');
            $savings = $txs->where('type', 'transfer')->whereNotNull('goal_id')->sum('amount');

            return [
                'category_id' => $categoryId,
                'category' => $category,
                'income' => $income,
                'expense' => $expense,
                'savings' => $savings,
                'net' => $income - $expense,
                'transaction_count' => $txs->count(),
            ];
        })->sortByDesc(fn ($item) => $item['expense'] + $item['savings'])->values();

        $totals = [
            'income' => $categoryBreakdown->sum('income'),
            'expense' => $categoryBreakdown->sum('expense'),
            'savings' => $categoryBreakdown->sum('savings'),
            'net' => $categoryBreakdown->sum('net'),
        ];

        return [
            'categoryBreakdown' => $categoryBreakdown,
            'categoryTotals' => $totals,
        ];
    }

    /**
     * Wallet Report - Per-wallet transaction history and balance changes.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $startDate  The start date of the report period
     * @param  string  $endDate  The end date of the report period
     * @return array<string, mixed>
     */
    private function getWalletReportData(Request $request, string $startDate, string $endDate): array
    {
        $wallets = Wallet::forUser()->with('transactions')->get();

        $walletData = $wallets->map(function ($wallet) use ($startDate, $endDate) {
            $periodTransactions = $wallet->transactions()
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $income = $periodTransactions->where('type', 'income')->sum('amount');
            $expense = $periodTransactions->where('type', 'expense')->sum('amount');

            $savings = $periodTransactions->where('type', 'transfer')
                ->whereNotNull('goal_id')
                ->sum('amount');
            $transfersOut = $periodTransactions->where('type', 'transfer')
                ->whereNull('goal_id')
                ->sum('amount');

            $transfersIn = Transaction::forUser()
                ->where('type', 'transfer')
                ->where('destination_wallet_id', $wallet->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            return [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'currency' => $wallet->currency,
                'current_balance' => $wallet->balance,
                'income' => $income,
                'expense' => $expense,
                'savings' => $savings,
                'transfers_in' => $transfersIn,
                'transfers_out' => $transfersOut,
                'net_change' => $income - $expense + $transfersIn - $transfersOut - $savings,
                'transaction_count' => $periodTransactions->count(),
            ];
        });

        $totals = [
            'income' => $walletData->sum('income'),
            'expense' => $walletData->sum('expense'),
            'savings' => $walletData->sum('savings'),
            'net_change' => $walletData->sum('net_change'),
        ];

        return [
            'walletData' => $walletData,
            'walletTotals' => $totals,
        ];
    }

    /**
     * Goal Report - Savings goal progress and projections.
     *
     * @param  Request  $request  The incoming request
     * @return array<string, mixed>
     */
    private function getGoalReportData(Request $request): array
    {
        $goals = TreasuryGoal::forUser()
            ->with(['category', 'wallet', 'transactions'])
            ->orderBy('deadline')
            ->get();

        $goalData = $goals->map(function ($goal) {
            $savedAmount = (float) $goal->saved_amount;
            $targetAmount = (float) $goal->target_amount;
            $remaining = max(0, $targetAmount - $savedAmount);
            $percentage = $goal->progress_percentage;

            $recentTransactions = $goal->transactions()
                ->where('date', '>=', now()->subMonths(3))
                ->get();

            $monthlyAverage = $recentTransactions->count() > 0
                ? $recentTransactions->sum('amount') / 3
                : 0;

            $projectedDate = null;
            if ($monthlyAverage > 0 && $remaining > 0) {
                $monthsToComplete = ceil($remaining / $monthlyAverage);
                $projectedDate = now()->addMonths((int) $monthsToComplete)->format('Y-m-d');
            }

            return [
                'id' => $goal->id,
                'name' => $goal->name,
                'category' => $goal->category,
                'wallet' => $goal->wallet,
                'target_amount' => $targetAmount,
                'saved_amount' => $savedAmount,
                'remaining' => $remaining,
                'percentage' => $percentage,
                'currency' => $goal->currency,
                'deadline' => $goal->deadline?->format('Y-m-d'),
                'is_completed' => $goal->is_completed,
                'is_overdue' => $goal->is_overdue,
                'monthly_average' => $monthlyAverage,
                'projected_completion' => $projectedDate,
            ];
        });

        $totals = [
            'target' => $goalData->sum('target_amount'),
            'saved' => $goalData->sum('saved_amount'),
            'remaining' => $goalData->sum('remaining'),
            'completed' => $goalData->where('is_completed', true)->count(),
            'active' => $goalData->where('is_completed', false)->count(),
        ];

        return [
            'goals' => $goalData,
            'goalTotals' => $totals,
        ];
    }

    /**
     * Summary Report - Period-based financial summaries (monthly/yearly).
     *
     * @param  Request  $request  The incoming request with optional group_by filter
     * @param  string  $startDate  The start date of the report period
     * @param  string  $endDate  The end date of the report period
     * @return array<string, mixed>
     */
    private function getSummaryReportData(Request $request, string $startDate, string $endDate): array
    {
        $groupBy = $request->input('group_by', 'month');

        $transactions = Transaction::forUser()
            ->where(function ($q) {
                $q->whereIn('type', ['income', 'expense'])
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'transfer')->whereNotNull('goal_id');
                    });
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $grouped = $transactions->groupBy(function ($tx) use ($groupBy) {
            return $groupBy === 'year'
                ? $tx->date->format('Y')
                : $tx->date->format('Y-m');
        });

        $summaryData = $grouped->map(function ($txs, $period) {
            $income = $txs->where('type', 'income')->sum('amount');
            $expense = $txs->where('type', 'expense')->sum('amount');
            $savings = $txs->where('type', 'transfer')->whereNotNull('goal_id')->sum('amount');
            $net = $income - $expense;

            return [
                'period' => $period,
                'income' => $income,
                'expense' => $expense,
                'savings' => $savings,
                'net' => $net,
                'savings_rate' => $income > 0 ? round(($savings / $income) * 100, 1) : 0,
                'transaction_count' => $txs->count(),
            ];
        })->sortKeys()->values();

        $totals = [
            'income' => $summaryData->sum('income'),
            'expense' => $summaryData->sum('expense'),
            'savings' => $summaryData->sum('savings'),
            'net' => $summaryData->sum('net'),
            'average_savings_rate' => $summaryData->avg('savings_rate'),
        ];

        return [
            'summaryData' => $summaryData,
            'summaryTotals' => $totals,
            'groupBy' => $groupBy,
        ];
    }

    /**
     * Export transactions to CSV.
     *
     * Streams a CSV download of filtered transactions for the given date range.
     *
     * @param  Request  $request  The incoming request with date range and optional filters
     * @return StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $query = Transaction::forUser()
            ->with(['wallet', 'destinationWallet', 'category'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc');

        if ($request->filled('wallet_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('wallet_id', $request->wallet_id)
                    ->orWhere('destination_wallet_id', $request->wallet_id);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $transactions = $query->get();

        $filename = sprintf('transactions_%s_to_%s.csv', $startDate, $endDate);

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Date',
                'Type',
                'Category',
                'Description',
                'Amount',
                'Currency',
                'Wallet',
                'Destination Wallet',
                'Notes',
            ]);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->date->format('Y-m-d'),
                    ucfirst($tx->type),
                    $tx->category?->name ?? '-',
                    $tx->description ?? '-',
                    $tx->amount,
                    $tx->wallet?->currency ?? '-',
                    $tx->wallet?->name ?? '-',
                    $tx->destinationWallet?->name ?? '-',
                    $tx->notes ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
