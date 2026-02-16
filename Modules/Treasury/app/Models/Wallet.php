<?php

/**
 * Wallet Model
 *
 * Represents a financial wallet (cash, bank, e-wallet, savings) that holds
 * a balance in a specific currency. Provides net worth calculations with
 * cross-currency conversion, formatted balance accessors, and activity scoping.
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
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasUserOwnership;
use Modules\Treasury\Database\Factories\WalletFactory;

/**
 * Class Wallet
 *
 * Financial wallet model with balance tracking and currency conversion.
 */
class Wallet extends Model
{
    use HasFactory, HasUserOwnership, HasUuids, LogsActivity;

    /**
     * Wallet types are now managed via the Categories system.
     * Use Category::where('type', 'wallet_type') to get available types.
     * The 'type' field stores the category slug (e.g., 'cash', 'bank', 'ewallet').
     */

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
        'name',
        'type',
        'balance',
        'currency',
        'description',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the wallet.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for this wallet.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope a query to only include active wallets.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate total net worth for a specific user.
     * This is the centralized method for net worth calculations.
     *
     * @param  string|null  $userId  User ID, defaults to authenticated user
     * @return float Total balance of all active wallets
     */
    public static function getNetWorthForUser(?string $userId = null): float
    {
        return static::getSummaryForUser($userId)['total'];
    }

    /**
     * Get summary data for a user's wallets.
     * Uses database aggregate functions for efficiency.
     *
     * @param  string|null  $userId  User ID, defaults to authenticated user
     * @return array{total: float, wallet_count: int}
     */
    public static function getSummaryForUser(?string $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        $wallets = static::where('user_id', $userId)->active()->get();

        $converter = app(\Modules\Treasury\Services\Exchange\CurrencyConverter::class);
        $total = 0.0;

        foreach ($wallets as $wallet) {
            if ($wallet->currency === $referenceCurrency) {
                $total += (float) $wallet->balance;
            } else {
                $converted = $converter->convert($wallet->balance, $wallet->currency, $referenceCurrency);
                $total += $converted ?? (float) $wallet->balance;
            }
        }

        return [
            'total' => $total,
            'wallet_count' => $wallets->count(),
        ];
    }

    /**
     * Get the formatted balance attribute.
     *
     * @return string
     */
    public function getFormattedBalanceAttribute(): string
    {
        $currency = $this->currency ?? settings('treasury_reference_currency', 'IDR');

        return app(\Modules\Treasury\Services\Support\CurrencyFormatter::class)
            ->format($this->balance, $currency);
    }

    /**
     * Get the balance converted to user's reference currency.
     *
     * @return float
     */
    public function getConvertedBalanceAttribute(): float
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        if ($this->currency === $referenceCurrency) {
            return (float) $this->balance;
        }

        $converter = app(\Modules\Treasury\Services\Exchange\CurrencyConverter::class);
        $converted = $converter->convert($this->balance, $this->currency, $referenceCurrency);

        return $converted ?? (float) $this->balance;
    }

    /**
     * Get the formatted balance in user's reference currency.
     *
     * @return string
     */
    public function getFormattedConvertedBalanceAttribute(): string
    {
        $referenceCurrency = settings('treasury_reference_currency', 'IDR');

        return app(\Modules\Treasury\Services\Support\CurrencyFormatter::class)
            ->format($this->converted_balance, $referenceCurrency);
    }

    /**
     * Get audit tags for this wallet.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        return ['treasury', 'wallet', $this->type ?? 'unknown'];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return WalletFactory
     */
    protected static function newFactory(): WalletFactory
    {
        return WalletFactory::new();
    }
}
