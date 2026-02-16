<?php

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;

class TransactionObserver
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly SignalHandlerRegistry $signalHandlerRegistry
    ) {}

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->updateWalletBalance();
        });

        // Check if any goals linked to affected wallets should be completed
        $this->checkGoalCompletion($transaction);

        // Dispatch to all registered signal handlers (before cache flush so handlers can access cached data)
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
     */
    public function updated(Transaction $transaction): void
    {
        $balanceFieldsChanged = $transaction->isDirty(['amount', 'type', 'wallet_id', 'destination_wallet_id']);

        if ($balanceFieldsChanged) {
            DB::transaction(function () use ($transaction) {
                // Get original values
                $originalAmount = (float) $transaction->getOriginal('amount');
                $originalType = $transaction->getOriginal('type');
                $originalWalletId = $transaction->getOriginal('wallet_id');
                $originalDestinationWalletId = $transaction->getOriginal('destination_wallet_id');

                // --- Revert original source wallet balance ---
                if ($originalWalletId) {
                    $originalChange = Transaction::calculateBalanceChange($originalAmount, $originalType);
                    if ($originalChange >= 0) {
                        Wallet::where('id', $originalWalletId)->decrement('balance', $originalChange);
                    } else {
                        Wallet::where('id', $originalWalletId)->increment('balance', abs($originalChange));
                    }
                }

                // --- Revert original destination wallet balance (for transfers) ---
                if ($originalType === 'transfer' && $originalDestinationWalletId) {
                    Wallet::where('id', $originalDestinationWalletId)->decrement('balance', $originalAmount);
                }

                // --- Apply new balance changes ---
                $transaction->updateWalletBalance();
            });

            // Check if any goals linked to affected wallets should be completed
            $this->checkGoalCompletion($transaction);

            $this->cacheService->flush('treasury', 'TransactionObserver');
        }

        // Re-dispatch signals if transaction was modified in ways that affect signals
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
     */
    public function deleted(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->revertWalletBalance();
        });

        // Dispatch deleted event for signal handlers to clean up cache if needed
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
     */
    private function checkGoalCompletion(Transaction $transaction): void
    {
        // Only check if transaction is allocated to a goal
        if (! $transaction->goal_id) {
            return;
        }

        // Use already-loaded relation if available, otherwise fetch
        $goal = $transaction->relationLoaded('goal')
            ? $transaction->goal
            : TreasuryGoal::find($transaction->goal_id);

        // Skip if goal not found or already completed
        if (! $goal || $goal->is_completed) {
            return;
        }

        // Refresh the goal to get the latest saved_amount after transaction changes
        $goal->refresh();

        $savedAmount = (float) $goal->saved_amount;
        $target = (float) $goal->target_amount;

        // Mark goal as completed if saved amount reaches or exceeds target
        if ($target > 0 && $savedAmount >= $target) {
            $goal->update(['is_completed' => true]);
        }
    }
}
