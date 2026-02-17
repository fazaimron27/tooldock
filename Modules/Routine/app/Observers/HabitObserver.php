<?php

/**
 * Habit Observer
 *
 * Observes Habit model lifecycle events to flush Routine caches
 * when habits are created, updated, or deleted.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Observers;

use App\Services\Cache\CacheService;
use Modules\Routine\Models\Habit;

/**
 * Class HabitObserver
 *
 * Handles cache invalidation for habit changes.
 */
class HabitObserver
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Handle the Habit "created" event.
     *
     * @param  Habit  $habit
     * @return void
     */
    public function created(Habit $habit): void
    {
        $this->cacheService->flush('routine', 'HabitObserver');
    }

    /**
     * Handle the Habit "updated" event.
     *
     * @param  Habit  $habit
     * @return void
     */
    public function updated(Habit $habit): void
    {
        $this->cacheService->flush('routine', 'HabitObserver');
    }

    /**
     * Handle the Habit "deleted" event.
     *
     * @param  Habit  $habit
     * @return void
     */
    public function deleted(Habit $habit): void
    {
        $this->cacheService->flush('routine', 'HabitObserver');
    }
}
