<?php

namespace Modules\Treasury\Services\Transaction;

use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

class TransactionCategoryService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter,
        private readonly TransactionQueryService $queryService
    ) {}

    /**
     * Get top expense categories for a specific month.
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

        // Group by category and convert currencies
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

        // Sort by total descending and limit
        usort($categoryTotals, fn ($a, $b) => $b['total'] <=> $a['total']);
        $categoryTotals = array_slice($categoryTotals, 0, $limit);

        return array_map(fn ($cat) => [
            'category' => $cat['name'],
            'color' => $cat['color'],
            'amount' => $cat['total'],
        ], $categoryTotals);
    }
}
