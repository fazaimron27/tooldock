<?php

namespace Modules\Treasury\Services\Wallet;

use Modules\Core\Models\User;
use Modules\Treasury\Models\Wallet;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

class WalletSummaryService
{
    public function __construct(
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /**
     * Get total net worth (sum of all wallet balances).
     * Uses centralized Wallet::getSummaryForUser when no wallet filter is applied.
     * Converts all balances to reference currency for accurate multi-currency aggregation.
     */
    public function getNetWorth(User $user, array $filters = []): array
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        // If filtering by specific wallet, calculate with currency conversion
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

        // Use centralized method for unfiltered net worth
        return Wallet::getSummaryForUser($user->id);
    }

    /**
     * Get all wallets with their balances.
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
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['wallet_id'])) {
            $query->where('id', $filters['wallet_id']);
        }
    }
}
