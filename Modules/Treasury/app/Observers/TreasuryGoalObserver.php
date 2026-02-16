<?php

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;

class TreasuryGoalObserver
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Handle the TreasuryGoal "created" event.
     * Check if goal should be immediately completed based on wallet balance.
     */
    public function created(TreasuryGoal $goal): void
    {
        $this->checkCompletion($goal);
        $this->cacheService->flush('treasury', 'TreasuryGoalObserver');
    }

    /**
     * Handle the TreasuryGoal "updated" event.
     * Check if goal should be completed when target_amount is lowered.
     */
    public function updated(TreasuryGoal $goal): void
    {
        // Only check if target_amount changed and goal is not already completed
        if ($goal->isDirty('target_amount') && ! $goal->is_completed) {
            $this->checkCompletion($goal);
        }

        $this->cacheService->flush('treasury', 'TreasuryGoalObserver');
    }

    /**
     * Handle the TreasuryGoal "deleted" event.
     * Orphan related transactions by setting goal_id to null.
     */
    public function deleted(TreasuryGoal $goal): void
    {
        // Orphan transactions that were allocated to this goal
        // This preserves transaction history while removing goal association
        Transaction::where('goal_id', $goal->id)->update(['goal_id' => null]);

        $this->cacheService->flush('treasury', 'TreasuryGoalObserver');
    }

    /**
     * Check if goal should be marked as completed based on allocated amount.
     */
    private function checkCompletion(TreasuryGoal $goal): void
    {
        // Skip if already completed
        if ($goal->is_completed) {
            return;
        }

        // Get saved amount from goal allocations
        $savedAmount = (float) $goal->saved_amount;
        $target = (float) $goal->target_amount;

        // Mark as completed if saved amount meets or exceeds target
        if ($target > 0 && $savedAmount >= $target) {
            // Use updateQuietly to avoid triggering another observer cycle
            $goal->updateQuietly(['is_completed' => true]);
        }
    }
}
