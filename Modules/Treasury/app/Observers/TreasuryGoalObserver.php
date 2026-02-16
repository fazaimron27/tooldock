<?php

/**
 * Treasury Goal Observer
 *
 * Observes TreasuryGoal model lifecycle events to check goal completion
 * status, orphan related transactions on deletion, and flush Treasury caches.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;

/**
 * Class TreasuryGoalObserver
 *
 * Handles goal completion checks and cache invalidation.
 */
class TreasuryGoalObserver
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Handle the TreasuryGoal "created" event.
     * Check if goal should be immediately completed based on wallet balance.
     *
     * @param  TreasuryGoal  $goal
     * @return void
     */
    public function created(TreasuryGoal $goal): void
    {
        $this->checkCompletion($goal);
        $this->cacheService->flush('treasury', 'TreasuryGoalObserver');
    }

    /**
     * Handle the TreasuryGoal "updated" event.
     * Check if goal should be completed when target_amount is lowered.
     *
     * @param  TreasuryGoal  $goal
     * @return void
     */
    public function updated(TreasuryGoal $goal): void
    {
        if ($goal->isDirty('target_amount') && ! $goal->is_completed) {
            $this->checkCompletion($goal);
        }

        $this->cacheService->flush('treasury', 'TreasuryGoalObserver');
    }

    /**
     * Handle the TreasuryGoal "deleted" event.
     * Orphan related transactions by setting goal_id to null.
     *
     * @param  TreasuryGoal  $goal
     * @return void
     */
    public function deleted(TreasuryGoal $goal): void
    {
        Transaction::where('goal_id', $goal->id)->update(['goal_id' => null]);

        $this->cacheService->flush('treasury', 'TreasuryGoalObserver');
    }

    /**
     * Check if goal should be marked as completed based on allocated amount.
     *
     * @param  TreasuryGoal  $goal
     * @return void
     */
    private function checkCompletion(TreasuryGoal $goal): void
    {
        if ($goal->is_completed) {
            return;
        }

        $savedAmount = (float) $goal->saved_amount;
        $target = (float) $goal->target_amount;

        if ($target > 0 && $savedAmount >= $target) {
            $goal->updateQuietly(['is_completed' => true]);
        }
    }
}
