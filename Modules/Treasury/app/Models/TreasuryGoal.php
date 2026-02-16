<?php

/**
 * Treasury Goal Model
 *
 * Represents a savings goal linked to a dedicated savings wallet. Tracks
 * progress through allocated transactions, calculates completion percentage,
 * remaining amounts, and overdue status based on optional deadlines.
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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Categories\Models\Category;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;
use Modules\Treasury\Database\Factories\TreasuryGoalFactory;

/**
 * Class TreasuryGoal
 *
 * Savings goal model with progress tracking and wallet linkage.
 */
class TreasuryGoal extends Model
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
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'name',
        'target_amount',
        'currency',
        'deadline',
        'description',
        'is_completed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['saved_amount'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'string',
            'deadline' => 'date:Y-m-d',
            'is_completed' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the goal.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet linked to this goal.
     *
     * @return BelongsTo
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the category for this goal.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the transactions allocated to this goal.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'goal_id');
    }

    /**
     * Scope a query to only include incomplete goals.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('is_completed', false);
    }

    /**
     * Get saved_amount from sum of transactions allocated to this goal.
     * This tracks progress per-goal, allowing multiple sequential goals on the same wallet.
     *
     * @return string
     */
    public function getSavedAmountAttribute(): string
    {
        $allocated = $this->transactions()->sum('amount');

        return (string) $allocated;
    }

    /**
     * Get the progress percentage attribute.
     *
     * @return float
     */
    public function getProgressPercentageAttribute(): float
    {
        $targetAmount = (float) $this->target_amount;
        $savedAmount = (float) $this->saved_amount;

        if ($targetAmount <= 0) {
            return 0;
        }

        return min(100, round(($savedAmount / $targetAmount) * 100, 1));
    }

    /**
     * Get the remaining amount attribute.
     *
     * @return float
     */
    public function getRemainingAmountAttribute(): float
    {
        $targetAmount = (float) $this->target_amount;
        $savedAmount = (float) $this->saved_amount;

        return max(0, $targetAmount - $savedAmount);
    }

    /**
     * Check if the goal is overdue.
     *
     * @return bool
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->deadline || $this->is_completed) {
            return false;
        }

        return $this->deadline->isPast();
    }

    /**
     * Get audit tags for this goal.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        $tags = ['treasury', 'goal'];

        if ($this->is_completed) {
            $tags[] = 'completed';
        }

        return $tags;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return TreasuryGoalFactory
     */
    protected static function newFactory(): TreasuryGoalFactory
    {
        return TreasuryGoalFactory::new();
    }
}
