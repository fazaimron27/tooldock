<?php

/**
 * Budget Period Observer
 *
 * Observes BudgetPeriod model lifecycle events to flush Treasury caches
 * and dispatch signal events when budget period amounts are changed or created.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\BudgetPeriod;

/**
 * Class BudgetPeriodObserver
 *
 * Handles cache invalidation and signal dispatch for budget period changes.
 */
class BudgetPeriodObserver
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly SignalHandlerRegistry $signalHandlerRegistry
    ) {}

    /**
     * Handle the BudgetPeriod "created" event.
     *
     * @param  BudgetPeriod  $budgetPeriod
     * @return void
     */
    public function created(BudgetPeriod $budgetPeriod): void
    {
        $this->dispatchSignal('budgetperiod.created', $budgetPeriod);
        $this->cacheService->flush('treasury', 'BudgetPeriodObserver');
    }

    /**
     * Handle the BudgetPeriod "updated" event.
     *
     * @param  BudgetPeriod  $budgetPeriod
     * @return void
     */
    public function updated(BudgetPeriod $budgetPeriod): void
    {
        if ($budgetPeriod->isDirty('amount')) {
            $this->dispatchSignal('budgetperiod.updated', $budgetPeriod);
        }
        $this->cacheService->flush('treasury', 'BudgetPeriodObserver');
    }

    /**
     * Handle the BudgetPeriod "deleted" event.
     *
     * @param  BudgetPeriod  $budgetPeriod
     * @return void
     */
    public function deleted(BudgetPeriod $budgetPeriod): void
    {
        $this->cacheService->flush('treasury', 'BudgetPeriodObserver');
    }

    /**
     * Dispatch signal to registered handlers.
     *
     * @param  string  $event
     * @param  BudgetPeriod  $budgetPeriod
     * @return void
     */
    private function dispatchSignal(string $event, BudgetPeriod $budgetPeriod): void
    {
        try {
            $budgetPeriod->loadMissing('budget.user');
            $user = $budgetPeriod->budget?->user;

            if (! $user) {
                return;
            }

            $this->signalHandlerRegistry->dispatch($event, [
                'budgetPeriod' => $budgetPeriod,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::debug("Signal dispatch ({$event}) failed: ".$e->getMessage());
            }
        }
    }
}
