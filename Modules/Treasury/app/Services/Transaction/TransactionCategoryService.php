<?php

/**
 * Transaction Category Service
 *
 * Handles aggregation of expense data by category for a given month,
 * with multi-currency conversion support.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Transaction;

use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

/**
 * Service for aggregating transaction data by category.
 */
class TransactionCategoryService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter,
        private readonly TransactionQueryService $queryService
    ) {}

    /**
     * Get top expense categories for a specific month.
     *
     * @param  User  $user
     * @param  int  $month
     * @param  int  $year
     * @param  int  $limit
     * @param  array  $filters
     * @return array
     */
    public function getTopExpenseCategories(User $user, int $month, int $year, int $limit = 50, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $query = Transaction::where('user_id', $user->id)
            ->with(['wallet:id,currency', 'category:id,name,color'])
            ->where('type', 'expense')
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        $this->queryService->applyFilters($query, $filters);

        $transactions = $query->get(['amount', 'wallet_id', 'category_id']);

        $categoryTotals = [];
        foreach ($transactions as $tx) {
            $categoryId = $tx->category_id ?? 'uncategorized';
            $walletCurrency = $tx->wallet?->currency ?? $referenceCurrency;
            $convertedAmount = $this->currencyConverter->convert(
                (float) $tx->amount,
                $walletCurrency,
                $referenceCurrency
            );

            if (! isset($categoryTotals[$categoryId])) {
                $categoryTotals[$categoryId] = [
                    'name' => $tx->category?->name ?? 'Uncategorized',
                    'color' => $tx->category?->color ?? '#cbd5e1',
                    'total' => 0.0,
                ];
            }

            $categoryTotals[$categoryId]['total'] += $convertedAmount;
        }

        usort($categoryTotals, fn ($a, $b) => $b['total'] <=> $a['total']);
        $categoryTotals = array_slice($categoryTotals, 0, $limit);

        return array_map(fn ($cat) => [
            'category' => $cat['name'],
            'color' => $cat['color'],
            'amount' => $cat['total'],
        ], $categoryTotals);
    }
}
