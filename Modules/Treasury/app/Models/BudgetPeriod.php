<?php

/**
 * Budget Period Model
 *
 * Represents a monthly instance of a budget template that tracks spending
 * for a specific month. Each period belongs to a Budget template and can
 * have customized amounts per month. Supports period navigation and formatting.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Categories\Models\Category;
use Modules\Core\Constants\Roles;
use Modules\Treasury\Database\Factories\BudgetPeriodFactory;

/**
 * Budget Period Model
 *
 * Represents a monthly instance of a budget template.
 * Each period tracks spending for a specific month.
 */
class BudgetPeriod extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'budget_periods';

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
     * @var list<string>
     */
    protected $fillable = [
        'budget_id',
        'period',
        'amount',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'string',
        ];
    }

    /**
     * Create a formatted period string (YYYY-MM).
     *
     * @param  int  $month  Month number (1-12)
     * @param  int  $year  Four-digit year
     * @return string
     */
    public static function formatPeriod(int $month, int $year): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    /**
     * Get the budget template this period belongs to.
     *
     * @return BelongsTo
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the category attribute.
     *
     * @return Category|null
     */
    public function getCategoryAttribute(): ?Category
    {
        return $this->budget?->category;
    }

    /**
     * Get the user ID attribute.
     *
     * @return string|null
     */
    public function getUserIdAttribute(): ?string
    {
        return $this->budget?->user_id;
    }

    /**
     * Get the user via budget relationship.
     * Used by SignalHandlerRegistry to extract user for notifications.
     *
     * @return \Modules\Core\Models\User|null
     */
    public function getUserAttribute(): ?\Modules\Core\Models\User
    {
        return $this->budget?->user;
    }

    /**
     * Scope a query to only include periods for the authenticated user.
     *
     * Super Admins can see all budget periods.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForUser(Builder $query): Builder
    {
        $user = request()->user();

        if ($user?->hasRole(Roles::SUPER_ADMIN)) {
            return $query;
        }

        return $query->whereHas('budget', function ($q) use ($user) {
            $q->where('user_id', $user?->id);
        });
    }

    /**
     * Scope a query to filter by period (YYYY-MM format).
     *
     * @param  Builder  $query
     * @param  string  $period  Period string in YYYY-MM format
     * @return Builder
     */
    public function scopeForPeriod(Builder $query, string $period): Builder
    {
        return $query->where('period', $period);
    }

    /**
     * Scope a query to filter by month and year.
     *
     * @param  Builder  $query
     * @param  int  $month  Month number (1-12)
     * @param  int  $year  Four-digit year
     * @return Builder
     */
    public function scopeForMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->where('period', self::formatPeriod($month, $year));
    }

    /**
     * Get audit tags for this budget period.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        return ['treasury', 'budget', 'budget-period'];
    }

    /**
     * Convert period string to Carbon date.
     *
     * @return \Carbon\Carbon
     */
    public function getPeriodDate(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();
    }

    /**
     * Get the previous period string (YYYY-MM format).
     *
     * @return string
     */
    public function getPreviousPeriod(): string
    {
        return $this->getPeriodDate()->subMonth()->format('Y-m');
    }

    /**
     * Get the next period string (YYYY-MM format).
     *
     * @return string
     */
    public function getNextPeriod(): string
    {
        return $this->getPeriodDate()->addMonth()->format('Y-m');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return BudgetPeriodFactory
     */
    protected static function newFactory(): BudgetPeriodFactory
    {
        return BudgetPeriodFactory::new();
    }
}
