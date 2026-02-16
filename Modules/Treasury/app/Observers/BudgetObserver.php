<?php

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\Budget;

class BudgetObserver
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly SignalHandlerRegistry $signalHandlerRegistry
    ) {}

    public function created(Budget $budget): void
    {
        $this->cacheService->flush('treasury', 'BudgetObserver');
    }

    public function updated(Budget $budget): void
    {
        // Dispatch signal if amount changed (budget limit adjustment)
        if ($budget->isDirty('amount')) {
            $this->dispatchSignal('budget.updated', $budget);
        }
        $this->cacheService->flush('treasury', 'BudgetObserver');
    }

    public function deleted(Budget $budget): void
    {
        $this->cacheService->flush('treasury', 'BudgetObserver');
    }

    /**
     * Dispatch signal to registered handlers.
     */
    private function dispatchSignal(string $event, Budget $budget): void
    {
        try {
            // Load the user relationship
            $budget->loadMissing('user');
            $user = $budget->user;

            if (! $user) {
                return;
            }

            // Pass data as array with user explicitly for SignalHandlerRegistry
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
