<?php

/**
 * Habit Service
 *
 * Encapsulates business logic for habit management including statistics
 * computation, completion toggling, and status transitions. Caches expensive
 * queries using the centralized CacheService with per-user scoped keys.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Services\Cache\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Routine\Models\Habit;
use Modules\Routine\Models\HabitLog;

/**
 * Class HabitService
 *
 * Provides habit-related business logic extracted from the controller
 * to maintain single-responsibility and improve testability.
 */
class HabitService
{
    private const CACHE_TTL_HOURS = 1;

    private const CACHE_TAG = 'routine';

    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Get all active habits with their logs for the last 365 days.
     *
     * Results are cached per-user for performance. Cache is automatically
     * invalidated when habits or logs are created, updated, or deleted.
     *
     * @return Collection
     */
    public function getActiveHabitsWithLogs(): Collection
    {
        $userId = Auth::id();

        return $this->cacheService->remember(
            "routine:{$userId}:active_habits_with_logs",
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Habit::forUser()
                ->active()
                ->with(['logs' => function ($query) {
                    $query->where('completed_at', '>=', Carbon::now()->subDays(365))
                        ->orderByDesc('completed_at');
                }])
                ->orderBy('created_at')
                ->get(),
            self::CACHE_TAG,
            'HabitService'
        );
    }

    /**
     * Get all inactive (paused/archived) habits for the current user.
     *
     * Results are cached per-user for performance.
     *
     * @return Collection
     */
    public function getInactiveHabits(): Collection
    {
        $userId = Auth::id();

        return $this->cacheService->remember(
            "routine:{$userId}:inactive_habits",
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Habit::forUser()
                ->whereIn('status', ['paused', 'archived'])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'name', 'icon', 'color', 'type', 'unit', 'target_value', 'goal_per_week', 'status']),
            self::CACHE_TAG,
            'HabitService'
        );
    }

    /**
     * Compute summary statistics from a collection of active habits.
     *
     * @param  Collection  $habits  Active habits with logs loaded
     * @return array{total_habits: int, best_streak: int, weekly_rate: float, weekly_completions: int, weekly_goal: int}
     */
    public function computeStats(Collection $habits): array
    {
        $totalHabits = $habits->count();
        $bestStreak = $habits->max('current_streak') ?? 0;

        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $totalWeeklyGoal = $habits->sum('goal_per_week');
        $weeklyCompletions = 0;

        foreach ($habits as $habit) {
            $weeklyCompletions += $habit->logs
                ->filter(fn ($log) => Carbon::parse($log->completed_at)->between($weekStart, $weekEnd))
                ->count();
        }

        $weeklyRate = $totalWeeklyGoal > 0
            ? round(($weeklyCompletions / $totalWeeklyGoal) * 100, 1)
            : 0;

        return [
            'total_habits' => $totalHabits,
            'best_streak' => $bestStreak,
            'weekly_rate' => $weeklyRate,
            'weekly_completions' => $weeklyCompletions,
            'weekly_goal' => $totalWeeklyGoal,
        ];
    }

    /**
     * Toggle a habit completion for a given date.
     *
     * For measurable habits, upserts or removes the log based on value.
     * For boolean habits, toggles the log on/off.
     *
     * @param  Habit  $habit  The habit to toggle
     * @param  array  $validated  Validated input with 'date' and optional 'value'
     * @return string|null Success message, or null if no action taken
     */
    public function toggleCompletion(Habit $habit, array $validated): ?string
    {
        $date = $validated['date'];

        if ($habit->type === 'measurable' && isset($validated['value'])) {
            return $this->toggleMeasurable($habit, $date, $validated['value']);
        }

        return $this->toggleBoolean($habit, $date);
    }

    /**
     * Toggle a measurable habit log entry.
     *
     * @param  Habit  $habit  The measurable habit
     * @param  string  $date  The completion date
     * @param  float  $value  The measured value
     * @return string|null Success message, or null if no action taken
     */
    protected function toggleMeasurable(Habit $habit, string $date, float $value): ?string
    {
        $log = HabitLog::where('habit_id', $habit->id)
            ->where('completed_at', $date)
            ->first();

        if ($log && $value == 0) {
            $log->delete();

            return 'Habit entry removed successfully.';
        }

        if ($log) {
            $log->update(['value' => $value]);

            return 'Habit entry updated successfully.';
        }

        if ($value > 0) {
            HabitLog::create([
                'habit_id' => $habit->id,
                'completed_at' => $date,
                'value' => $value,
            ]);

            return 'Habit entry logged successfully.';
        }

        return null;
    }

    /**
     * Toggle a boolean habit log entry.
     *
     * @param  Habit  $habit  The boolean habit
     * @param  string  $date  The completion date
     * @return string Success message
     */
    protected function toggleBoolean(Habit $habit, string $date): string
    {
        $existing = HabitLog::where('habit_id', $habit->id)
            ->where('completed_at', $date)
            ->first();

        if ($existing) {
            $existing->delete();

            return 'Habit unmarked successfully.';
        }

        HabitLog::create([
            'habit_id' => $habit->id,
            'completed_at' => $date,
        ]);

        return 'Habit marked successfully.';
    }

    /**
     * Handle a habit status transition with streak preservation.
     *
     * When pausing, the current streak is frozen. When resuming from pause,
     * the frozen streak data is kept so the streak calculation can bridge
     * the gap. When archiving, streak data is cleared.
     *
     * @param  Habit  $habit  The habit being transitioned
     * @param  string  $newStatus  The target status
     * @param  array  $validated  Validated input data
     * @return void
     */
    public function handleStatusTransition(Habit $habit, string $newStatus, array $validated): void
    {
        if ($newStatus === 'paused') {
            $validated['paused_at'] = Carbon::today()->toDateString();
            $validated['resumed_at'] = null;
            $validated['streak_at_pause'] = $habit->current_streak;
        } elseif ($newStatus === 'active' && $habit->status === 'paused') {
            $validated['resumed_at'] = Carbon::today()->toDateString();
        } elseif ($newStatus === 'active' && $habit->status === 'archived') {
            $validated['paused_at'] = null;
            $validated['resumed_at'] = null;
            $validated['streak_at_pause'] = null;
        } elseif ($newStatus === 'archived') {
            $validated['paused_at'] = null;
            $validated['resumed_at'] = null;
            $validated['streak_at_pause'] = null;
        }

        $habit->update($validated);
    }

    /**
     * Get aggregated heatmap data for the last 365 days.
     *
     * Results are cached per-user for performance.
     *
     * @return Collection Collection of {date, count} objects
     */
    public function getHeatmapData(): Collection
    {
        $userId = Auth::id();

        return $this->cacheService->remember(
            "routine:{$userId}:heatmap_data",
            now()->addHours(self::CACHE_TTL_HOURS),
            function () {
                $since = Carbon::now()->subDays(365)->toDateString();
                $habitIds = Habit::forUser()->active()->pluck('id');

                return HabitLog::whereIn('habit_id', $habitIds)
                    ->where('completed_at', '>=', $since)
                    ->selectRaw('completed_at as date, count(*) as count')
                    ->groupBy('completed_at')
                    ->orderBy('completed_at')
                    ->get();
            },
            self::CACHE_TAG,
            'HabitService'
        );
    }
}
