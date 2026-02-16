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
use Modules\Treasury\Database\Factories\BudgetFactory;

/**
 * Budget Template Model
 *
 * Represents a recurring budget template that defines monthly spending limits.
 * Each template can automatically generate monthly BudgetPeriod instances.
 */
class Budget extends Model
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
        'category_id',
        'amount',
        'currency',
        'is_active',
        'is_recurring',
        'rollover_enabled',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Use string to avoid float precision issues in JavaScript
            'amount' => 'string',
            'is_active' => 'boolean',
            'is_recurring' => 'boolean',
            'rollover_enabled' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the budget template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this budget template.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all budget periods (monthly instances) for this template.
     */
    public function periods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    /**
     * Get the budget period for a specific month.
     */
    public function getPeriod(string $period): ?BudgetPeriod
    {
        return $this->periods()->where('period', $period)->first();
    }

    /**
     * Scope a query to only include active budgets.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include recurring budgets.
     */
    public function scopeRecurring(Builder $query): Builder
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Get audit tags for this budget.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        return ['treasury', 'budget'];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BudgetFactory
    {
        return BudgetFactory::new();
    }
}
