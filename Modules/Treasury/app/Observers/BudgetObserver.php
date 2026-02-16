<?php

/**
 * Budget Observer
 *
 * Observes Budget model lifecycle events to flush Treasury caches
 * and dispatch signal events when budget amounts are changed.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Budget;

/**
 * Class BudgetObserver
 *
 * Handles cache invalidation and signal dispatch for budget changes.
 */
class BudgetObserver
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly SignalHandlerRegistry $signalHandlerRegistry
    ) {}

    /**
     * Handle the Budget "created" event.
     *
     * @param  Budget  $budget
     * @return void
     */
    public function created(Budget $budget): void
    {
        $this->cacheService->flush('treasury', 'BudgetObserver');
    }

    /**
     * Handle the Budget "updated" event.
     *
     * @param  Budget  $budget
     * @return void
     */
    public function updated(Budget $budget): void
    {
        if ($budget->isDirty('amount')) {
            $this->dispatchSignal('budget.updated', $budget);
        }
        $this->cacheService->flush('treasury', 'BudgetObserver');
    }

    /**
     * Handle the Budget "deleted" event.
     *
     * @param  Budget  $budget
     * @return void
     */
    public function deleted(Budget $budget): void
    {
        $this->cacheService->flush('treasury', 'BudgetObserver');
    }

    /**
     * Dispatch signal to registered handlers.
     *
     * @param  string  $event
     * @param  Budget  $budget
     * @return void
     */
    private function dispatchSignal(string $event, Budget $budget): void
    {
        try {
            $budget->loadMissing('user');
            $user = $budget->user;

            if (! $user) {
                return;
            }

            $this->signalHandlerRegistry->dispatch($event, [
                'budget' => $budget,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::debug("Signal dispatch ({$event}) failed: ".$e->getMessage());
            }
        }
    }
}
