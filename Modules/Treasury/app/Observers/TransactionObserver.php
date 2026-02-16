<?php

/**
 * Transaction Observer
 *
 * Observes Transaction model lifecycle events to update wallet balances
 * atomically, check goal completion status, dispatch signal events,
 * and flush Treasury caches.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;

/**
 * Class TransactionObserver
 *
 * Handles wallet balance updates, goal completion checks, and signal dispatch.
 */
class TransactionObserver
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly SignalHandlerRegistry $signalHandlerRegistry
    ) {}

    /**
     * Handle the Transaction "created" event.
     *
     * @param  Transaction  $transaction
     * @return void
     */
    public function created(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->updateWalletBalance();
        });

        $this->checkGoalCompletion($transaction);

        try {
            $this->signalHandlerRegistry->dispatch('transaction.created', $transaction);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::debug('Signal dispatch failed: '.$e->getMessage());
            }
        }

        $this->cacheService->flush('treasury', 'TransactionObserver');
    }

    /**
     * Handle the Transaction "updated" event.
     * Handles changes to amount, type, wallet_id, destination_wallet_id.
     *
     * @param  Transaction  $transaction
     * @return void
     */
    public function updated(Transaction $transaction): void
    {
        $balanceFieldsChanged = $transaction->isDirty(['amount', 'type', 'wallet_id', 'destination_wallet_id']);

        if ($balanceFieldsChanged) {
            DB::transaction(function () use ($transaction) {
                $originalAmount = (float) $transaction->getOriginal('amount');
                $originalType = $transaction->getOriginal('type');
                $originalWalletId = $transaction->getOriginal('wallet_id');
                $originalDestinationWalletId = $transaction->getOriginal('destination_wallet_id');
                if ($originalWalletId) {
                    $originalChange = Transaction::calculateBalanceChange($originalAmount, $originalType);
                    if ($originalChange >= 0) {
                        Wallet::where('id', $originalWalletId)->decrement('balance', $originalChange);
                    } else {
                        Wallet::where('id', $originalWalletId)->increment('balance', abs($originalChange));
                    }
                }

                if ($originalType === 'transfer' && $originalDestinationWalletId) {
                    Wallet::where('id', $originalDestinationWalletId)->decrement('balance', $originalAmount);
                }
                $transaction->updateWalletBalance();
            });

            $this->checkGoalCompletion($transaction);

            $this->cacheService->flush('treasury', 'TransactionObserver');
        }
        if ($transaction->isDirty(['amount', 'category_id', 'date', 'type', 'wallet_id'])) {
            try {
                $this->signalHandlerRegistry->dispatch('transaction.updated', $transaction);
            } catch (\Exception $e) {
                Log::debug('Signal dispatch on update failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Handle the Transaction "deleted" event.
     *
     * @param  Transaction  $transaction
     * @return void
     */
    public function deleted(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->revertWalletBalance();
        });

        try {
            $this->signalHandlerRegistry->dispatch('transaction.deleted', $transaction);
        } catch (\Exception $e) {
            Log::debug('Signal dispatch on delete failed: '.$e->getMessage());
        }

        $this->cacheService->flush('treasury', 'TransactionObserver');
    }

    /**
     * Check if the goal linked to this transaction should be marked as completed.
     *
     * Uses the goal's saved_amount (sum of allocations) instead of wallet balance.
     * Optimized to use already-loaded relation when available.
     *
     * @param  Transaction  $transaction
     * @return void
     */
    private function checkGoalCompletion(Transaction $transaction): void
    {
        if (! $transaction->goal_id) {
            return;
        }
        $goal = $transaction->relationLoaded('goal')
            ? $transaction->goal
            : TreasuryGoal::find($transaction->goal_id);

        if (! $goal || $goal->is_completed) {
            return;
        }
        $goal->refresh();

        $savedAmount = (float) $goal->saved_amount;
        $target = (float) $goal->target_amount;

        if ($target > 0 && $savedAmount >= $target) {
            $goal->update(['is_completed' => true]);
        }
    }
}
