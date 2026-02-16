<?php

/**
 * Transaction Model
 *
 * Represents a financial transaction (income, expense, or transfer) within
 * the Treasury module. Handles wallet balance updates atomically, supports
 * cross-currency transfers with exchange rates, and provides database-agnostic
 * date scoping for reports.
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
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;
use Modules\Media\Models\MediaFile;
use Modules\Treasury\Database\Factories\TransactionFactory;

/**
 * Class Transaction
 *
 * Financial transaction model with wallet balance management and reporting scopes.
 */
class Transaction extends Model
{
    use HasFactory, HasUserOwnership, HasUuids, LogsActivity;

    /**
     * Available transaction types.
     *
     * @var array<string>
     */
    public const TYPES = ['income', 'expense', 'transfer'];

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
        'destination_wallet_id',
        'category_id',
        'goal_id',
        'type',
        'name',
        'amount',
        'fee',
        'exchange_rate',
        'original_currency',
        'description',
        'date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * Uses 'decimal:10' for exchange_rate to preserve precision from DECIMAL(20,10) column.
     * This prevents floating-point precision loss during currency conversions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'string',
            'fee' => 'string',
            'exchange_rate' => 'decimal:10',
            'date' => 'datetime',
        ];
    }

    /**
     * Calculate the balance change for a given amount and type.
     *
     * @param  float  $amount  Transaction amount
     * @param  string  $type  Transaction type (income, expense, transfer)
     * @return float
     */
    public static function calculateBalanceChange(float $amount, string $type): float
    {
        return match ($type) {
            'income' => $amount,
            'expense' => -$amount,
            'transfer' => -$amount,
            default => 0,
        };
    }

    /**
     * Update the wallet balance after transaction creation.
     * Uses atomic database operations to prevent race conditions.
     *
     * Fee handling by transaction type:
     * - Income: Net credited = amount - fee (fee is deducted from earnings, e.g., tax)
     * - Expense: Total deducted = amount + fee (fee is additional cost)
     * - Transfer: Total deducted from source = amount + fee, destination receives full amount
     */
    public function updateWalletBalance(): void
    {
        if ($this->wallet_id) {
            $amount = (float) $this->amount;
            $fee = (float) ($this->fee ?? 0);
            $totalChange = match ($this->type) {
                'income' => $amount - $fee,
                'expense' => -($amount + $fee),
                'transfer' => -($amount + $fee),
                default => 0,
            };

            if ($totalChange >= 0) {
                Wallet::where('id', $this->wallet_id)->increment('balance', $totalChange);
            } else {
                Wallet::where('id', $this->wallet_id)->decrement('balance', abs($totalChange));
            }
        }

        if ($this->type === 'transfer' && $this->destination_wallet_id) {
            $destinationAmount = (float) $this->amount;

            if ($this->exchange_rate && $this->exchange_rate != 1.0) {
                $destinationAmount = (float) $this->amount * $this->exchange_rate;
            }

            Wallet::where('id', $this->destination_wallet_id)->increment('balance', $destinationAmount);
        }
    }

    /**
     * Revert the wallet balance on transaction deletion.
     * Uses atomic database operations to prevent race conditions.
     *
     * This reverses the fee logic applied in updateWalletBalance().
     */
    public function revertWalletBalance(): void
    {
        if ($this->wallet_id) {
            $amount = (float) $this->amount;
            $fee = (float) ($this->fee ?? 0);
            $originalChange = match ($this->type) {
                'income' => $amount - $fee,
                'expense' => -($amount + $fee),
                'transfer' => -($amount + $fee),
                default => 0,
            };
            if ($originalChange >= 0) {
                Wallet::where('id', $this->wallet_id)->decrement('balance', $originalChange);
            } else {
                Wallet::where('id', $this->wallet_id)->increment('balance', abs($originalChange));
            }
        }

        if ($this->type === 'transfer' && $this->destination_wallet_id) {
            $destinationAmount = (float) $this->amount;

            if ($this->exchange_rate && $this->exchange_rate != 1.0) {
                $destinationAmount = (float) $this->amount * $this->exchange_rate;
            }

            Wallet::where('id', $this->destination_wallet_id)->decrement('balance', $destinationAmount);
        }
    }

    /**
     * Get the user that owns the transaction.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet for this transaction (source wallet).
     *
     * @return BelongsTo
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the destination wallet for transfers.
     *
     * @return BelongsTo
     */
    public function destinationWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'destination_wallet_id');
    }

    /**
     * Get the category for this transaction.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the goal this transaction is allocated to.
     *
     * @return BelongsTo
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(TreasuryGoal::class, 'goal_id');
    }

    /**
     * Get the attachments for this transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(MediaFile::class, 'model');
    }

    /**
     * Scope a query to filter by transaction type.
     *
     * @param  Builder  $query
     * @param  string  $type  Transaction type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param  Builder  $query
     * @param  mixed  $startDate  Start date
     * @param  mixed  $endDate  End date
     * @return Builder
     */
    public function scopeInDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by month and year.
     *
     * @param  Builder  $query
     * @param  int  $month  Month number (1-12)
     * @param  int  $year  Four-digit year
     * @return Builder
     */
    public function scopeInMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }

    /**
     * Scope to select the date formatted as a period string (YYYY-MM).
     *
     * This provides database-agnostic date formatting for grouping transactions by month.
     * Supports PostgreSQL, SQLite, and MySQL/MariaDB.
     */
    public function scopeSelectPeriod(Builder $query): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        $format = match ($driver) {
            'pgsql' => "TO_CHAR(date, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', date)",
            default => "DATE_FORMAT(date, '%Y-%m')",
        };

        return $query->selectRaw("$format as period");
    }

    /**
     * Scope to select the hour from created_at timestamp (database-agnostic).
     *
     * Supports PostgreSQL, SQLite, and MySQL/MariaDB.
     */
    public function scopeSelectHour(Builder $query, array $additionalColumns = []): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        $hourExpr = match ($driver) {
            'pgsql' => 'EXTRACT(HOUR FROM created_at)',
            'sqlite' => "CAST(strftime('%H', created_at) AS INTEGER)",
            default => 'HOUR(created_at)',
        };

        $columns = array_merge(["$hourExpr as hour_val"], $additionalColumns);

        return $query->selectRaw(implode(', ', $columns));
    }

    /**
     * Scope to select the month from date column (database-agnostic).
     *
     * Supports PostgreSQL, SQLite, and MySQL/MariaDB.
     */
    public function scopeSelectMonth(Builder $query, array $additionalColumns = []): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        $monthExpr = match ($driver) {
            'pgsql' => 'EXTRACT(MONTH FROM date)::INTEGER',
            'sqlite' => "CAST(strftime('%m', date) AS INTEGER)",
            default => 'MONTH(date)',
        };

        $columns = array_merge(["$monthExpr as month_val"], $additionalColumns);

        return $query->selectRaw(implode(', ', $columns));
    }

    /**
     * Scope to select the month key (YYYY-MM) with additional columns (database-agnostic).
     *
     * Supports PostgreSQL, SQLite, and MySQL/MariaDB.
     */
    public function scopeSelectMonthKey(Builder $query, array $additionalColumns = []): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        $monthKeyExpr = match ($driver) {
            'pgsql' => "TO_CHAR(date, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', date)",
            default => "DATE_FORMAT(date, '%Y-%m')",
        };

        $columns = array_merge(["$monthKeyExpr as month_key"], $additionalColumns);

        return $query->selectRaw(implode(', ', $columns));
    }

    /**
     * Get audit tags for this transaction.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        $tags = ['treasury', 'transaction', $this->type ?? 'unknown'];

        if ($this->goal_id) {
            $tags[] = 'goal-allocation';
        }

        return $tags;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return TransactionFactory
     */
    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
