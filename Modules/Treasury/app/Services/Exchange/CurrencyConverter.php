<?php

/**
 * Currency Converter Service
 *
 * Handles currency conversions using the "Gold Standard" cross-rate
 * calculation pattern via BCMath for arbitrary precision arithmetic.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Exchange;

use Modules\Treasury\Models\ExchangeRate;

/**
 * Currency Converter Service
 *
 * Handles currency conversions using the "Gold Standard" cross-rate calculation pattern.
 * All rates are stored relative to USD, enabling conversion between any two currencies
 * while preventing triangular arbitrage through consistent precision handling.
 *
 * Key Design Decisions:
 * - Uses BCMath for arbitrary precision arithmetic to prevent float errors
 * - Always truncates (floors) final amounts to prevent arbitrage exploitation
 * - Maintains high internal precision (10 decimals) during calculations
 *
 * Formula: amount_in_target = amount_in_source × (target_rate / source_rate)
 */
class CurrencyConverter
{
    /**
     * Internal calculation precision scale for BCMath operations.
     */
    private const CALCULATION_SCALE = 10;

    /**
     * Default decimal places for monetary amounts.
     */
    private const AMOUNT_DECIMALS = 2;

    /**
     * High precision decimal places (for crypto, etc.).
     */
    private const HIGH_PRECISION_DECIMALS = 8;

    /**
     * Convert an amount from one currency to another using BCMath precision.
     *
     * Uses cross-rate calculation via USD as the intermediary.
     * Always truncates to prevent round-trip arbitrage exploitation.
     *
     * @param  float|string  $amount  The amount to convert
     * @param  string  $from  Source currency code (e.g., 'SGD')
     * @param  string  $to  Target currency code (e.g., 'IDR')
     * @param  int  $decimals  Output decimal places (default: 2)
     * @return float|null Converted amount, or null if rates unavailable
     */
    public function convert(float|string $amount, string $from, string $to, int $decimals = self::AMOUNT_DECIMALS): ?float
    {
        if ($from === $to) {
            return (float) $this->truncate((string) $amount, $decimals);
        }

        $fromRate = ExchangeRate::getRateAsString($from);
        $toRate = ExchangeRate::getRateAsString($to);

        if ($fromRate === null || $toRate === null) {
            return null;
        }

        $result = bcdiv(
            bcmul((string) $amount, $toRate, self::CALCULATION_SCALE),
            $fromRate,
            self::CALCULATION_SCALE
        );

        return (float) $this->truncate($result, $decimals);
    }

    /**
     * Convert with high precision (for cryptocurrencies, etc.).
     *
     * @param  float|string  $amount  The amount to convert
     * @param  string  $from  Source currency code
     * @param  string  $to  Target currency code
     * @return float|null Converted amount with 8 decimal places
     */
    public function convertHighPrecision(float|string $amount, string $from, string $to): ?float
    {
        return $this->convert($amount, $from, $to, self::HIGH_PRECISION_DECIMALS);
    }

    /**
     * Convert an amount to the user's reference currency.
     *
     * @param  float|string  $amount  The amount to convert
     * @param  string  $fromCurrency  Source currency code
     * @param  string|null  $userId  User ID, defaults to authenticated user
     * @return float|null Converted amount in reference currency
     */
    public function toReference(float|string $amount, string $fromCurrency, ?string $userId = null): ?float
    {
        $referenceCurrency = $this->getReferenceCurrency($userId);

        return $this->convert($amount, $fromCurrency, $referenceCurrency);
    }

    /**
     * Convert from reference currency to another currency.
     *
     * @param  float|string  $amount  Amount in reference currency
     * @param  string  $toCurrency  Target currency code
     * @param  string|null  $userId  User ID, defaults to authenticated user
     * @return float|null Converted amount in target currency
     */
    public function fromReference(float|string $amount, string $toCurrency, ?string $userId = null): ?float
    {
        $referenceCurrency = $this->getReferenceCurrency($userId);

        return $this->convert($amount, $referenceCurrency, $toCurrency);
    }

    /**
     * Get the exchange rate between two currencies using BCMath precision.
     *
     * @param  string  $from  Source currency code
     * @param  string  $to  Target currency code
     * @return float|null Exchange rate, or null if rates unavailable
     */
    public function getRate(string $from, string $to): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        $fromRate = ExchangeRate::getRateAsString($from);
        $toRate = ExchangeRate::getRateAsString($to);

        if ($fromRate === null || $toRate === null) {
            return null;
        }

        return (float) bcdiv($toRate, $fromRate, self::CALCULATION_SCALE);
    }

    /**
     * Get the exchange rate as a string for maximum precision.
     *
     * @param  string  $from  Source currency code
     * @param  string  $to  Target currency code
     * @return string|null Exchange rate as string, or null if unavailable
     */
    public function getRateAsString(string $from, string $to): ?string
    {
        if ($from === $to) {
            return '1.0000000000';
        }

        $fromRate = ExchangeRate::getRateAsString($from);
        $toRate = ExchangeRate::getRateAsString($to);

        if ($fromRate === null || $toRate === null) {
            return null;
        }

        return bcdiv($toRate, $fromRate, self::CALCULATION_SCALE);
    }

    /**
     * Get the user's reference currency.
     *
     * @param  string|null  $userId  Defaults to authenticated user
     * @return string ISO 4217 currency code
     */
    public function getReferenceCurrency(?string $userId = null): string
    {
        return settings('treasury_reference_currency', 'IDR');
    }

    /**
     * Check if conversion is available between two currencies.
     *
     * @param  string  $from  Source currency
     * @param  string  $to  Target currency
     * @return bool True if both currencies have rates available
     */
    public function canConvert(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return ExchangeRate::getRate($from) !== null
            && ExchangeRate::getRate($to) !== null;
    }

    /**
     * Validate that round-trip conversion doesn't create money (anti-arbitrage check).
     *
     * Tests: A → B → A should yield <= original amount.
     *
     * @param  string  $currency1  First currency
     * @param  string  $currency2  Second currency
     * @param  string  $testAmount  Amount to test (default: '1000.00')
     * @return array{valid: bool, original: string, roundTrip: string, difference: string}
     */
    public function validateNoArbitrage(string $currency1, string $currency2, string $testAmount = '1000.00'): array
    {
        $rate1to2 = $this->getRateAsString($currency1, $currency2);
        $rate2to1 = $this->getRateAsString($currency2, $currency1);

        if ($rate1to2 === null || $rate2to1 === null) {
            return [
                'valid' => false,
                'original' => $testAmount,
                'roundTrip' => '0',
                'difference' => '0',
                'error' => 'Rates not available',
            ];
        }

        $inCurrency2 = $this->convert($testAmount, $currency1, $currency2);
        $backToCurrency1 = $this->convert($inCurrency2, $currency2, $currency1);

        $difference = bcsub((string) $backToCurrency1, $testAmount, self::CALCULATION_SCALE);

        return [
            'valid' => bccomp($difference, '0', self::CALCULATION_SCALE) <= 0,
            'original' => $testAmount,
            'roundTrip' => (string) $backToCurrency1,
            'difference' => $difference,
        ];
    }

    /**
     * Validate rate consistency (rate × inverse ≈ 1).
     *
     * @param  string  $currency1  First currency
     * @param  string  $currency2  Second currency
     * @param  string  $tolerance  Maximum acceptable deviation from 1.0
     * @return array{consistent: bool, product: string, deviation: string}
     */
    public function validateRateConsistency(string $currency1, string $currency2, string $tolerance = '0.0000001'): array
    {
        $rate1to2 = $this->getRateAsString($currency1, $currency2);
        $rate2to1 = $this->getRateAsString($currency2, $currency1);

        if ($rate1to2 === null || $rate2to1 === null) {
            return [
                'consistent' => false,
                'product' => '0',
                'deviation' => '0',
                'error' => 'Rates not available',
            ];
        }

        $product = bcmul($rate1to2, $rate2to1, self::CALCULATION_SCALE);
        $deviation = bcsub($product, '1', self::CALCULATION_SCALE);
        $absDeviation = ltrim($deviation, '-');

        return [
            'consistent' => bccomp($absDeviation, $tolerance, self::CALCULATION_SCALE) <= 0,
            'product' => $product,
            'deviation' => $deviation,
        ];
    }

    /**
     * Truncate a number to specified decimal places (always rounds down).
     *
     * This is the key anti-arbitrage measure: by always truncating,
     * we ensure that round-trip conversions can never create money.
     *
     * @param  string  $value  The value to truncate
     * @param  int  $decimals  Number of decimal places
     * @return string Truncated value
     */
    private function truncate(string $value, int $decimals): string
    {
        if ($decimals < 0) {
            $decimals = 0;
        }

        return bcdiv($value, '1', $decimals);
    }
}
