<?php

/**
 * Transaction Query Service
 *
 * Provides reusable query building and filtering for transaction data,
 * including recent transaction retrieval and global filter application.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Transaction;

use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;

/**
 * Service for querying and filtering transaction data.
 */
class TransactionQueryService
{
    /**
     * Get recent transactions.
     *
     * @param  User  $user
     * @param  int  $limit
     * @param  array  $filters
     * @return array
     */
    public function getRecent(User $user, int $limit = 10, array $filters = []): array
    {
        $query = Transaction::where('user_id', $user->id)
            ->with(['wallet', 'destinationWallet', 'category', 'attachments', 'goal.category']);

        $this->applyFilters($query, $filters);

        $filteredWalletId = $filters['wallet_id'] ?? null;

        return $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($tx) use ($filteredWalletId) {
                $isIncomingTransfer = $filteredWalletId
                    && $tx->type === 'transfer'
                    && $tx->destination_wallet_id === $filteredWalletId;

                $convertedAmount = null;
                if ($isIncomingTransfer && $tx->exchange_rate && $tx->exchange_rate != 1) {
                    $convertedAmount = bcmul((string) $tx->amount, (string) $tx->exchange_rate, 2);
                }

                return [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'name' => $tx->name,
                    'title' => $tx->name ?: ($tx->description ?: 'Untitled Transaction'),
                    'amount' => (float) $tx->amount,
                    'fee' => (float) $tx->fee,
                    'description' => $tx->description,
                    'date' => $tx->date->format('Y-m-d'),
                    'timestamp' => $tx->date->diffForHumans(),
                    'icon' => $tx->type === 'income' ? 'TrendingUp' : 'TrendingDown',
                    'iconColor' => $tx->type === 'income' ? 'text-emerald-500' : 'text-rose-500',
                    'exchange_rate' => $tx->exchange_rate ? (float) $tx->exchange_rate : null,
                    'is_incoming_transfer' => $isIncomingTransfer,
                    'converted_amount' => $convertedAmount ? (float) $convertedAmount : null,
                    'wallet' => $tx->wallet ? [
                        'id' => $tx->wallet->id,
                        'name' => $tx->wallet->name,
                        'currency' => $tx->wallet->currency,
                    ] : null,
                    'destination_wallet' => $tx->destinationWallet ? [
                        'id' => $tx->destinationWallet->id,
                        'name' => $tx->destinationWallet->name,
                        'currency' => $tx->destinationWallet->currency,
                    ] : null,
                    'category' => $tx->category ? [
                        'id' => $tx->category->id,
                        'name' => $tx->category->name,
                        'slug' => $tx->category->slug,
                        'color' => $tx->category->color,
                    ] : null,
                    'goal' => $tx->goal ? [
                        'id' => $tx->goal->id,
                        'name' => $tx->goal->name,
                        'category' => $tx->goal->category ? [
                            'slug' => $tx->goal->category->slug,
                            'color' => $tx->goal->category->color,
                        ] : null,
                    ] : null,
                    'attachments' => $tx->attachments->map(fn ($a) => [
                        'id' => $a->id,
                        'filename' => $a->filename,
                    ])->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Apply global filters to a query.
     * Uses qualified column names for Transaction queries to support JOINs.
     *
     * @param  mixed  $query
     * @param  array  $filters
     * @return void
     */
    public function applyFilters($query, array $filters): void
    {
        $model = $query->getModel();
        $isTransaction = $model instanceof Transaction;

        if (! empty($filters['wallet_id'])) {
            if ($model instanceof Wallet) {
                $query->where('id', $filters['wallet_id']);
            } else {
                $walletColumn = $isTransaction ? 'transactions.wallet_id' : 'wallet_id';
                $destColumn = $isTransaction ? 'transactions.destination_wallet_id' : 'destination_wallet_id';
                $query->where(function ($q) use ($walletColumn, $destColumn, $filters) {
                    $q->where($walletColumn, $filters['wallet_id'])
                        ->orWhere($destColumn, $filters['wallet_id']);
                });
            }
        }

        $hasDateColumn = ! ($model instanceof Wallet) && ! ($model instanceof TreasuryGoal);

        if ($hasDateColumn) {
            $dateColumn = $isTransaction ? 'transactions.date' : 'date';

            if (! empty($filters['date_from'])) {
                $query->where($dateColumn, '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->where($dateColumn, '<=', $filters['date_to']);
            }
        }
    }
}
