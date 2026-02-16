<?php

/**
 * Wallet Summary Service
 *
 * Provides wallet balance summaries and net worth calculations
 * with multi-currency conversion support.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Wallet;

use Modules\Core\Models\User;
use Modules\Treasury\Models\Wallet;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

/**
 * Service for wallet balance summaries and net worth calculations.
 */
class WalletSummaryService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /**
     * Get total net worth (sum of all wallet balances).
     * Uses centralized Wallet::getSummaryForUser when no wallet filter is applied.
     * Converts all balances to reference currency for accurate multi-currency aggregation.
     *
     * @param  User  $user
     * @param  array  $filters
     * @return array{total: float, wallet_count: int}
     */
    public function getNetWorth(User $user, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        if (! empty($filters['wallet_id'])) {
            $query = Wallet::where('user_id', $user->id)->active();
            $this->applyFilters($query, $filters);

            $wallets = $query->get(['balance', 'currency']);
            $total = 0.0;

            foreach ($wallets as $wallet) {
                if ($wallet->currency === $referenceCurrency) {
                    $total += (float) $wallet->balance;
                } else {
                    $converted = $this->currencyConverter->convert(
                        (float) $wallet->balance,
                        $wallet->currency,
                        $referenceCurrency
                    );
                    $total += $converted ?? (float) $wallet->balance;
                }
            }

            return [
                'total' => $total,
                'wallet_count' => $wallets->count(),
            ];
        }

        return Wallet::getSummaryForUser($user->id);
    }

    /**
     * Get all wallets with their balances.
     *
     * @param  User  $user
     * @param  array  $filters
     * @return array
     */
    public function getWallets(User $user, array $filters = []): array
    {
        $query = Wallet::where('user_id', $user->id)->active();
        $this->applyFilters($query, $filters);

        return $query->orderBy('balance', 'desc')
            ->get()
            ->map(fn ($wallet) => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type,
                'currency' => $wallet->currency,
                'balance' => (float) $wallet->balance,
            ])
            ->toArray();
    }

    /**
     * Apply filters to wallet queries.
     *
     * @param  mixed  $query
     * @param  array  $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['wallet_id'])) {
            $query->where('id', $filters['wallet_id']);
        }
    }
}
