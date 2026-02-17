<?php

/**
 * Habit Log Observer
 *
 * Observes HabitLog lifecycle events to flush Routine caches and dispatch
 * streak milestone signals when habit completion logs are created or deleted.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Observers;

use App\Services\Cache\CacheService;
use App\Services\Core\UserPreferenceService;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Support\Facades\Log;
use Modules\Routine\Models\HabitLog;

/**
 * Class HabitLogObserver
 *
 * Flushes the Routine cache and dispatches the `routine.streak.updated` signal
 * after log creation or deletion so that cached data is refreshed and streak
 * milestone notifications can be triggered. Respects user preferences for
 * streak milestone notifications.
 */
class HabitLogObserver
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly SignalHandlerRegistry $signalHandlerRegistry,
        private readonly UserPreferenceService $userPreferenceService
    ) {}

    /**
     * Handle the HabitLog "created" event.
     *
     * @param  HabitLog  $log
     * @return void
     */
    public function created(HabitLog $log): void
    {
        $this->cacheService->flush('routine', 'HabitLogObserver');
        $this->dispatchStreakSignal($log);
    }

    /**
     * Handle the HabitLog "updated" event.
     *
     * @param  HabitLog  $log
     * @return void
     */
    public function updated(HabitLog $log): void
    {
        $this->cacheService->flush('routine', 'HabitLogObserver');
    }

    /**
     * Handle the HabitLog "deleted" event.
     *
     * @param  HabitLog  $log
     * @return void
     */
    public function deleted(HabitLog $log): void
    {
        $this->cacheService->flush('routine', 'HabitLogObserver');
        $this->dispatchStreakSignal($log);
    }

    /**
     * Dispatch the streak updated signal for the habit's owner.
     *
     * Loads the habit (with user) and dispatches the signal so
     * StreakMilestoneHandler can check for milestone thresholds.
     * Respects the `routine_streak_milestone_notify` user preference.
     *
     * @param  HabitLog  $log
     * @return void
     */
    private function dispatchStreakSignal(HabitLog $log): void
    {
        try {
            $habit = $log->relationLoaded('habit')
                ? $log->habit
                : $log->habit()->with('user')->first();

            if (! $habit || $habit->status !== 'active') {
                return;
            }

            if (! $habit->relationLoaded('user')) {
                $habit->load('user');
            }

            $streakNotifyEnabled = $this->userPreferenceService->get(
                $habit->user,
                'routine_streak_milestone_notify',
                true
            );

            if (! filter_var($streakNotifyEnabled, FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            $this->signalHandlerRegistry->dispatch('routine.streak.updated', [
                'user' => $habit->user,
                'streak' => $habit->current_streak,
                'habit_name' => $habit->name,
            ]);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::debug('Routine streak signal dispatch failed: '.$e->getMessage());
            }
        }
    }
}
