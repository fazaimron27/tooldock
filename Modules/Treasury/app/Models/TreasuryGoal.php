<?php

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
            // Use string to avoid float precision issues in JavaScript
            'target_amount' => 'string',
            'deadline' => 'date:Y-m-d',
            'is_completed' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the goal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet linked to this goal.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the category for this goal.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the transactions allocated to this goal.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'goal_id');
    }

    /**
     * Scope a query to only include incomplete goals.
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('is_completed', false);
    }

    /**
     * Get saved_amount from sum of transactions allocated to this goal.
     * This tracks progress per-goal, allowing multiple sequential goals on the same wallet.
     */
    public function getSavedAmountAttribute(): string
    {
        // Sum all transactions explicitly allocated to this goal
        $allocated = $this->transactions()->sum('amount');

        return (string) $allocated;
    }

    /**
     * Get the progress percentage attribute.
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
     */
    public function getRemainingAmountAttribute(): float
    {
        $targetAmount = (float) $this->target_amount;
        $savedAmount = (float) $this->saved_amount;

        return max(0, $targetAmount - $savedAmount);
    }

    /**
     * Check if the goal is overdue.
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
     */
    protected static function newFactory(): TreasuryGoalFactory
    {
        return TreasuryGoalFactory::new();
    }
}
