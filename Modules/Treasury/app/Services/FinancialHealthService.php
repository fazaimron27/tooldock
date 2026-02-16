<?php

namespace Modules\Treasury\Services;

use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

/**
 * Service for calculating financial health metrics and goal recommendations.
 *
 * Provides financial guidance based on user's transaction history for
 * Emergency & Security goal categories.
 */
class FinancialHealthService
{
    /**
     * Category slugs that belong to Emergency & Security parent.
     */
    private const EMERGENCY_SECURITY_SLUGS = [
        'shield-check' => 'emergency_fund',
        'shield-plus' => 'insurance_fund',
        'briefcase' => 'job_loss_fund',
    ];

    /**
     * Minimum months of data required for reliable recommendations.
     */
    private const MIN_MONTHS_FOR_RECOMMENDATION = 3;

    public function __construct(
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /**
     * Get average monthly expenses over the specified period.
     *
     * Converts all expenses to reference currency for accurate calculation.
     */
    public function getAverageMonthlyExpenses(User $user, int $months = 6): float
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $endDate = now()->endOfMonth();
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        $transactions = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('type', 'expense')
            ->get(['amount', 'wallet_id', 'date']);

        if ($transactions->isEmpty()) {
            return 0.0;
        }

        $totalExpense = 0.0;
        foreach ($transactions as $tx) {
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $totalExpense += $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );
        }

        // Count actual months with data
        $monthsWithData = $transactions->groupBy(fn ($tx) => $tx->date->format('Y-m'))->count();

        return $monthsWithData > 0 ? $totalExpense / $monthsWithData : 0.0;
    }

    /**
     * Get average monthly income over the specified period.
     *
     * Converts all income to reference currency for accurate calculation.
     */
    public function getAverageMonthlyIncome(User $user, int $months = 6): float
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $endDate = now()->endOfMonth();
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        $transactions = Transaction::where('user_id', $user->id)
            ->with('wallet:id,currency')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('type', 'income')
            ->get(['amount', 'wallet_id', 'date']);

        if ($transactions->isEmpty()) {
            return 0.0;
        }

        $totalIncome = 0.0;
        foreach ($transactions as $tx) {
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $totalIncome += $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );
        }

        // Count actual months with data
        $monthsWithData = $transactions->groupBy(fn ($tx) => $tx->date->format('Y-m'))->count();

        return $monthsWithData > 0 ? $totalIncome / $monthsWithData : 0.0;
    }

    /**
     * Check if user has enough transaction data for recommendations.
     *
     * @return array{expenses: bool, income: bool, expense_months: int, income_months: int}
     */
    public function hasEnoughData(User $user): array
    {
        $endDate = now()->endOfMonth();
        $startDate = now()->subMonths(5)->startOfMonth();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('type', ['income', 'expense'])
            ->get(['type', 'date']);

        $expenseMonths = $transactions
            ->where('type', 'expense')
            ->groupBy(fn ($tx) => $tx->date->format('Y-m'))
            ->count();

        $incomeMonths = $transactions
            ->where('type', 'income')
            ->groupBy(fn ($tx) => $tx->date->format('Y-m'))
            ->count();

        return [
            'expenses' => $expenseMonths >= self::MIN_MONTHS_FOR_RECOMMENDATION,
            'income' => $incomeMonths >= self::MIN_MONTHS_FOR_RECOMMENDATION,
            'expense_months' => $expenseMonths,
            'income_months' => $incomeMonths,
        ];
    }

    /**
     * Get goal recommendation based on category slug.
     *
     * Returns null if:
     * - Category is not an Emergency & Security category
     * - User doesn't have enough data for the recommendation type
     *
     * @return array{
     *   category_type: string,
     *   avg_expense: float,
     *   avg_income: float,
     *   months_analyzed: int,
     *   suggestions: array,
     *   description: string,
     *   tip: string,
     *   formula: string
     * }|null
     */
    public function getGoalRecommendation(User $user, string $categorySlug, ?string $walletCurrency = null): ?array
    {
        // Check if this is an Emergency & Security category
        if (! isset(self::EMERGENCY_SECURITY_SLUGS[$categorySlug])) {
            return null;
        }

        $categoryType = self::EMERGENCY_SECURITY_SLUGS[$categorySlug];
        $dataStatus = $this->hasEnoughData($user);
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        // Use wallet currency if provided, otherwise use reference currency
        $targetCurrency = $walletCurrency ?: $referenceCurrency;

        // Emergency Fund and Job Loss Fund need expense data
        // Insurance Fund needs income data
        $needsExpenseData = in_array($categoryType, ['emergency_fund', 'job_loss_fund']);
        $needsIncomeData = $categoryType === 'insurance_fund';

        // If insufficient data, return guidance instead of null
        if ($needsExpenseData && ! $dataStatus['expenses']) {
            return $this->buildInsufficientDataResponse(
                $categoryType,
                'expense',
                $dataStatus['expense_months'],
                $targetCurrency
            );
        }

        if ($needsIncomeData && ! $dataStatus['income']) {
            return $this->buildInsufficientDataResponse(
                $categoryType,
                'income',
                $dataStatus['income_months'],
                $targetCurrency
            );
        }

        $avgExpense = $this->getAverageMonthlyExpenses($user);
        $avgIncome = $this->getAverageMonthlyIncome($user);

        $recommendation = match ($categoryType) {
            'emergency_fund' => $this->buildEmergencyFundRecommendation($avgExpense, $dataStatus['expense_months'], $referenceCurrency),
            'job_loss_fund' => $this->buildJobLossFundRecommendation($avgExpense, $dataStatus['expense_months'], $referenceCurrency),
            'insurance_fund' => $this->buildInsuranceFundRecommendation($avgIncome, $dataStatus['income_months'], $referenceCurrency),
            default => null,
        };

        // Convert amounts to wallet currency if different from reference
        if ($recommendation && $targetCurrency !== $referenceCurrency) {
            $recommendation = $this->convertRecommendationCurrency($recommendation, $referenceCurrency, $targetCurrency);
        }

        return $recommendation;
    }

    /**
     * Convert recommendation amounts from reference currency to target currency.
     */
    private function convertRecommendationCurrency(array $recommendation, string $fromCurrency, string $toCurrency): array
    {
        // Store original reference values for display
        $recommendation['reference_currency'] = $fromCurrency;
        $recommendation['reference_avg_expense'] = $recommendation['avg_expense'];
        $recommendation['reference_avg_income'] = $recommendation['avg_income'];

        // Convert average values
        if ($recommendation['avg_expense'] > 0) {
            $recommendation['avg_expense'] = $this->currencyConverter->convert(
                $recommendation['avg_expense'],
                $fromCurrency,
                $toCurrency
            );
        }

        if ($recommendation['avg_income'] > 0) {
            $recommendation['avg_income'] = $this->currencyConverter->convert(
                $recommendation['avg_income'],
                $fromCurrency,
                $toCurrency
            );
        }

        // Convert suggestion amounts
        foreach ($recommendation['suggestions'] as &$suggestion) {
            $suggestion['reference_amount'] = $suggestion['amount'];
            $suggestion['amount'] = round($this->currencyConverter->convert(
                $suggestion['amount'],
                $fromCurrency,
                $toCurrency
            ), 2);
        }

        // Update currency to target
        $recommendation['currency'] = $toCurrency;

        return $recommendation;
    }

    /**
     * Build response for when user doesn't have enough transaction data.
     */
    private function buildInsufficientDataResponse(
        string $categoryType,
        string $dataType,
        int $currentMonths,
        string $currency
    ): array {
        $minMonths = self::MIN_MONTHS_FOR_RECOMMENDATION;
        $neededMonths = $minMonths - $currentMonths;
        $dataLabel = $dataType === 'expense' ? 'expense' : 'income';

        $categoryLabels = [
            'emergency_fund' => 'Emergency Fund',
            'job_loss_fund' => 'Job Loss Fund',
            'insurance_fund' => 'Insurance Fund',
        ];

        return [
            'category_type' => $categoryType,
            'has_sufficient_data' => false,
            'current_months' => $currentMonths,
            'required_months' => $minMonths,
            'currency' => $currency,
            'suggestions' => [],
            'description' => sprintf(
                'To provide accurate %s recommendations, we need at least %d months of %s data. You currently have %d month(s) of data.',
                $categoryLabels[$categoryType] ?? $categoryType,
                $minMonths,
                $dataLabel,
                $currentMonths
            ),
            'tip' => sprintf(
                'Start tracking your %s transactions regularly. Once you have %d more month(s) of data, we\'ll be able to calculate personalized target recommendations based on your spending patterns.',
                $dataLabel,
                $neededMonths > 0 ? $neededMonths : 1
            ),
            'action_label' => 'Add Transaction',
            'action_url' => route('treasury.transactions.create'),
        ];
    }

    /**
     * Build Emergency Fund recommendation.
     * Formula: 3-6 months × monthly expenses
     */
    private function buildEmergencyFundRecommendation(float $avgExpense, int $monthsAnalyzed, string $currency): array
    {
        return [
            'category_type' => 'emergency_fund',
            'avg_expense' => $avgExpense,
            'avg_income' => 0,
            'months_analyzed' => $monthsAnalyzed,
            'currency' => $currency,
            'suggestions' => [
                [
                    'label' => 'Conservative (3 months)',
                    'amount' => round($avgExpense * 3, 2),
                    'multiplier' => 3,
                    'months_to_build' => 12,
                ],
                [
                    'label' => 'Recommended (6 months)',
                    'amount' => round($avgExpense * 6, 2),
                    'multiplier' => 6,
                    'months_to_build' => 18,
                ],
            ],
            'description' => 'An Emergency Fund covers unexpected expenses like urgent repairs or medical bills. Financial experts recommend saving 3–6 months of your monthly expenses.',
            'tip' => 'Start by automating a fixed monthly transfer to this fund. Keep it in a high-yield savings account for easy access. Aim to save 10-20% of your income monthly until you reach your target.',
            'formula' => '3–6 × Monthly Expenses',
        ];
    }

    /**
     * Build Job Loss Fund recommendation.
     * Formula: 6-12 months × monthly expenses
     */
    private function buildJobLossFundRecommendation(float $avgExpense, int $monthsAnalyzed, string $currency): array
    {
        return [
            'category_type' => 'job_loss_fund',
            'avg_expense' => $avgExpense,
            'avg_income' => 0,
            'months_analyzed' => $monthsAnalyzed,
            'currency' => $currency,
            'suggestions' => [
                [
                    'label' => 'Minimum (6 months)',
                    'amount' => round($avgExpense * 6, 2),
                    'multiplier' => 6,
                    'months_to_build' => 24,
                ],
                [
                    'label' => 'Recommended (12 months)',
                    'amount' => round($avgExpense * 12, 2),
                    'multiplier' => 12,
                    'months_to_build' => 36,
                ],
            ],
            'description' => 'A Job Loss Fund serves as a bridge during unemployment, giving you time to find new opportunities without financial stress. Experts recommend saving 6–12 months of living expenses.',
            'tip' => 'Build this fund gradually alongside your Emergency Fund. Consider keeping it in a separate savings or money market account. Review and adjust the target annually based on your job stability and industry.',
            'formula' => '6–12 × Monthly Expenses',
        ];
    }

    /**
     * Build Insurance Fund recommendation.
     * Formula: ≤10% of monthly income (annual target)
     */
    private function buildInsuranceFundRecommendation(float $avgIncome, int $monthsAnalyzed, string $currency): array
    {
        $monthlyAllocation = round($avgIncome * 0.10, 2);
        $annualTarget = round($monthlyAllocation * 12, 2);

        return [
            'category_type' => 'insurance_fund',
            'avg_expense' => 0,
            'avg_income' => $avgIncome,
            'months_analyzed' => $monthsAnalyzed,
            'currency' => $currency,
            'suggestions' => [
                [
                    'label' => 'Monthly Allocation (10%)',
                    'amount' => $monthlyAllocation,
                    'multiplier' => 0.10,
                    'months_to_build' => 1,
                ],
                [
                    'label' => 'Annual Target',
                    'amount' => $annualTarget,
                    'multiplier' => 1.20,
                    'months_to_build' => 12,
                ],
            ],
            'description' => 'An Insurance Fund helps you maintain protection against catastrophic risks. Financial advisors recommend allocating no more than 10% of your income toward insurance premiums.',
            'tip' => 'Prioritize health insurance first, then life and property insurance. Review your coverage annually and compare providers to ensure you are getting the best value. Consider bundling policies for discounts.',
            'formula' => '≤10% × Monthly Income',
        ];
    }
}
