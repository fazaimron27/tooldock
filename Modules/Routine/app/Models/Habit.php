<?php

/**
 * Habit Model
 *
 * Represents a trackable habit with streak and completion calculations.
 * Supports both boolean (check/uncheck) and measurable (numeric value) types.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;

/**
 * Class Habit
 *
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $type
 * @property string $icon
 * @property string $color
 * @property int $goal_per_week
 * @property string|null $unit
 * @property float|null $target_value
 * @property string $status
 * @property string|null $paused_at
 * @property string|null $resumed_at
 * @property int|null $streak_at_pause
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read int $current_streak
 * @property-read float $completion_rate
 * @property-read bool $is_measurable
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<HabitLog> $logs
 */
class Habit extends Model
{
    use HasFactory, HasUserOwnership, HasUuids, LogsActivity;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
        'color',
        'goal_per_week',
        'unit',
        'target_value',
        'status',
        'paused_at',
        'resumed_at',
        'streak_at_pause',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'goal_per_week' => 'integer',
            'target_value' => 'decimal:2',
            'paused_at' => 'date',
            'resumed_at' => 'date',
            'streak_at_pause' => 'integer',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'current_streak',
        'completion_rate',
        'is_measurable',
    ];

    /**
     * Check if the habit is a measurable type.
     *
     * @return bool
     */
    public function getIsMeasurableAttribute(): bool
    {
        return $this->type === 'measurable';
    }

    /**
     * Get the user that owns the habit.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the completion logs for the habit.
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    /**
     * Scope a query to only include active habits.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include paused habits.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope a query to only include archived habits.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Determine if a log entry counts as "completed" for streak/rate calculations.
     *
     * For boolean habits, any log entry counts.
     * For measurable habits, any logged value greater than zero counts.
     *
     * @param  HabitLog  $log
     * @return bool
     */
    public function isLogCompleted(HabitLog $log): bool
    {
        if ($this->is_measurable) {
            return ($log->value ?? 0) > 0;
        }

        return true;
    }

    /**
     * Calculate the current consecutive streak.
     *
     * For daily habits (goal>=7): counts consecutive days with completion.
     * For non-daily habits: counts consecutive weeks where the weekly goal was met.
     * For measurable habits: any logged value greater than zero counts.
     *
     * @return int
     */
    public function getCurrentStreakAttribute(): int
    {
        $logs = $this->relationLoaded('logs')
            ? $this->logs->sortByDesc('completed_at')
            : $this->logs()->orderByDesc('completed_at')->get(['completed_at', 'value']);

        if ($logs->isEmpty()) {
            return $this->getFrozenStreakIfPaused();
        }

        $completedDates = $logs
            ->filter(fn ($log) => $this->isLogCompleted($log))
            ->map(fn ($log) => Carbon::parse($log->completed_at)->toDateString())
            ->unique()
            ->values();

        if ($completedDates->isEmpty()) {
            return $this->getFrozenStreakIfPaused();
        }

        return $this->calculateDailyStreak($completedDates);
    }

    /**
     * Return the frozen streak value if the habit is currently paused.
     *
     * @return int
     */
    protected function getFrozenStreakIfPaused(): int
    {
        if ($this->status === 'paused' && $this->streak_at_pause) {
            return $this->streak_at_pause;
        }

        return 0;
    }

    /**
     * Check whether a gap date falls within the most recent pause period.
     *
     * If the habit was paused and then resumed, dates between paused_at
     * and resumed_at are considered non-breaking for streak purposes.
     *
     * @param  Carbon  $gapDate  The first date without a completion
     * @return bool
     */
    protected function isDateInPausePeriod(Carbon $gapDate): bool
    {
        if (! $this->paused_at || ! $this->resumed_at || ! $this->streak_at_pause) {
            return false;
        }

        $pauseStart = Carbon::parse($this->paused_at);
        $pauseEnd = Carbon::parse($this->resumed_at);

        return $gapDate->between($pauseStart, $pauseEnd);
    }

    /**
     * Calculate daily streak with pause-period bridging.
     *
     * Walks backwards from today counting consecutive completed days.
     * If the chain breaks at a date that falls within a pause period,
     * the frozen streak is added to bridge the gap.
     *
     * @param  \Illuminate\Support\Collection  $completedDates
     * @return int
     */
    protected function calculateDailyStreak($completedDates): int
    {
        $streak = 0;
        $checkDate = Carbon::today();

        if (! $completedDates->contains($checkDate->toDateString())) {
            $checkDate = Carbon::yesterday();
        }

        while ($completedDates->contains($checkDate->toDateString())) {
            $streak++;
            $checkDate->subDay();
        }

        if ($streak > 0 && $this->isDateInPausePeriod($checkDate)) {
            $streak += $this->streak_at_pause;
        }

        return $streak;
    }

    /**
     * Calculate weekly streak with pause-period bridging.
     *
     * Counts consecutive weeks where the weekly goal was met.
     * If the chain breaks at a week that overlaps a pause period,
     * the frozen streak is added to bridge the gap.
     *
     * @param  \Illuminate\Support\Collection  $completedDates
     * @return int
     */
    protected function calculateWeeklyStreak($completedDates): int
    {
        $streak = 0;
        $weekStart = Carbon::today()->startOfWeek();

        $currentWeekCompleted = $completedDates->filter(
            fn ($d) => Carbon::parse($d)->between($weekStart, Carbon::today())
        )->count();

        if ($currentWeekCompleted >= $this->goal_per_week) {
            $streak++;
        }

        $checkWeek = $weekStart->copy()->subWeek();

        while (true) {
            $weekEnd = $checkWeek->copy()->endOfWeek();
            $weekCompleted = $completedDates->filter(
                fn ($d) => Carbon::parse($d)->between($checkWeek, $weekEnd)
            )->count();

            if ($weekCompleted >= $this->goal_per_week) {
                $streak++;
                $checkWeek->subWeek();
            } else {
                break;
            }
        }

        if ($streak > 0 && $this->paused_at && $this->resumed_at && $this->streak_at_pause) {
            $pauseStart = Carbon::parse($this->paused_at)->startOfWeek();
            if ($checkWeek->between($pauseStart, Carbon::parse($this->resumed_at))) {
                $streak += $this->streak_at_pause;
            }
        }

        return $streak;
    }

    /**
     * Calculate the completion rate as a percentage over the last 4 weeks.
     *
     * Measures actual completions against expected completions
     * based on goal_per_week. For measurable habits, any logged
     * value greater than zero counts as a completion.
     *
     * @return float
     */
    public function getCompletionRateAttribute(): float
    {
        $weeksToCheck = 4;
        $now = Carbon::today();
        $since = $now->copy()->subWeeks($weeksToCheck)->startOfWeek();

        $logs = $this->relationLoaded('logs')
            ? $this->logs->where('completed_at', '>=', $since)
            : $this->logs()->where('completed_at', '>=', $since->toDateString())->get(['completed_at', 'value']);

        $completedCount = $logs->filter(fn ($log) => $this->isLogCompleted($log))->count();

        $expectedCompletions = $this->goal_per_week * $weeksToCheck;

        if ($expectedCompletions <= 0) {
            return 0;
        }

        return round(min(100, ($completedCount / $expectedCompletions) * 100), 1);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Routine\Database\Factories\HabitFactory::new();
    }
}
