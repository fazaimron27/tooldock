<?php

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\BudgetPeriod;

class BudgetPeriodObserver
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly SignalHandlerRegistry $signalHandlerRegistry
    ) {}

    /**
     * Handle the BudgetPeriod "created" event.
     */
    public function created(BudgetPeriod $budgetPeriod): void
    {
        $this->dispatchSignal('budgetperiod.created', $budgetPeriod);
        $this->cacheService->flush('treasury', 'BudgetPeriodObserver');
    }

    /**
     * Handle the BudgetPeriod "updated" event.
     */
    public function updated(BudgetPeriod $budgetPeriod): void
    {
        // Only dispatch if amount changed (budget limit adjustment)
        if ($budgetPeriod->isDirty('amount')) {
            $this->dispatchSignal('budgetperiod.updated', $budgetPeriod);
        }
        $this->cacheService->flush('treasury', 'BudgetPeriodObserver');
    }

    /**
     * Handle the BudgetPeriod "deleted" event.
     */
    public function deleted(BudgetPeriod $budgetPeriod): void
    {
        $this->cacheService->flush('treasury', 'BudgetPeriodObserver');
    }

    /**
     * Dispatch signal to registered handlers.
     */
    private function dispatchSignal(string $event, BudgetPeriod $budgetPeriod): void
    {
        try {
            // Load the budget relationship to get user
            $budgetPeriod->loadMissing('budget.user');
            $user = $budgetPeriod->budget?->user;

            if (! $user) {
                return;
            }

            // Pass data as array with user explicitly for SignalHandlerRegistry
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
