<?php

/**
 * Exchange Rate Model
 *
 * Stores exchange rates relative to USD for cross-currency conversions.
 * Uses USD as the base currency (The "Gold Standard" pattern) since
 * ExchangeRate-API provides all rates relative to USD.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Exchange Rate Model
 *
 * Stores exchange rates relative to USD for cross-currency conversions.
 * Uses USD as the base currency (The "Gold Standard" pattern) since
 * ExchangeRate-API provides all rates relative to USD.
 *
 * Key Design Decisions:
 * - Uses 'decimal:10' cast to preserve database precision
 * - Provides getRateAsString() for BCMath operations
 * - Prevents triangular arbitrage through consistent rate handling
 */
class ExchangeRate extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'currency_code';

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
        'currency_code',
        'rate_to_usd',
        'fetched_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * Uses 'decimal:10' to preserve the full precision from DECIMAL(20,10) column.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_to_usd' => 'decimal:10',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * Get the exchange rate for a specific currency as float.
     *
     * Note: For BCMath operations, use getRateAsString() instead.
     *
     * @param  string  $currencyCode  ISO 4217 currency code
     * @return float|null Rate to USD, or null if not found
     */
    public static function getRate(string $currencyCode): ?float
    {
        if ($currencyCode === 'USD') {
            return 1.0;
        }

        $rate = static::find($currencyCode);

        return $rate ? (float) $rate->rate_to_usd : null;
    }

    /**
     * Get the exchange rate for a specific currency as string (preserves precision).
     *
     * Use this for BCMath operations to maintain full decimal precision.
     *
     * @param  string  $currencyCode  ISO 4217 currency code
     * @return string|null Rate to USD as string, or null if not found
     */
    public static function getRateAsString(string $currencyCode): ?string
    {
        if ($currencyCode === 'USD') {
            return '1.0000000000';
        }

        $rate = DB::table('exchange_rates')
            ->where('currency_code', $currencyCode)
            ->value('rate_to_usd');

        return $rate !== null ? (string) $rate : null;
    }

    /**
     * Get all rates as strings for BCMath operations.
     *
     * @return array<string, string> Currency code => rate as string
     */
    public static function getAllRatesAsStrings(): array
    {
        $rates = DB::table('exchange_rates')
            ->pluck('rate_to_usd', 'currency_code')
            ->map(fn ($rate) => (string) $rate)
            ->toArray();

        $rates['USD'] = '1.0000000000';

        return $rates;
    }

    /**
     * Check if rates are stale (older than specified hours).
     *
     * @param  int  $hours  Number of hours to consider stale (default: 24)
     * @return bool True if rates need refreshing
     */
    public static function isStale(int $hours = 24): bool
    {
        $latestRate = static::orderBy('fetched_at', 'desc')->first();

        if (! $latestRate || ! $latestRate->fetched_at) {
            return true;
        }

        return $latestRate->fetched_at->diffInHours(Carbon::now()) >= $hours;
    }

    /**
     * Get the timestamp of the last rate fetch.
     *
     * @return Carbon|null
     */
    public static function getLastFetchedAt(): ?Carbon
    {
        return static::orderBy('fetched_at', 'desc')->value('fetched_at');
    }
}
