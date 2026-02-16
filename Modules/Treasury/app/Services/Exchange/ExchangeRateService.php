<?php

namespace Modules\Treasury\Services\Exchange;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Models\ExchangeRate;

/**
 * Exchange Rate Service
 *
 * Handles fetching and caching exchange rates from ExchangeRate-API.
 * Uses USD as the base currency for cross-rate conversions.
 *
 * @see https://www.exchangerate-api.com/docs/overview
 */
class ExchangeRateService
{
    /**
     * Base URL for ExchangeRate-API
     */
    private const API_BASE_URL = 'https://v6.exchangerate-api.com/v6';

    /**
     * Refresh exchange rates from the API.
     *
     * @param  bool  $force  Force refresh even if not stale
     * @return array{success: bool, message: string, rates_updated?: int}
     */
    public function refreshRates(bool $force = false): array
    {
        $staleHours = config('treasury.exchange_rate_refresh_hours', 24);

        if (! $force && ! ExchangeRate::isStale($staleHours)) {
            return [
                'success' => true,
                'message' => 'Rates are still fresh, skipping refresh.',
            ];
        }

        $apiKey = config('treasury.exchange_rate_api_key');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Exchange rate API key not configured. Set EXCHANGE_RATE_API_KEY in .env',
            ];
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(30)
                ->get(self::API_BASE_URL."/{$apiKey}/latest/USD");

            if (! $response->successful()) {
                Log::error('Exchange rate API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'API request failed with status: '.$response->status(),
                ];
            }

            $data = $response->json();

            if ($data['result'] !== 'success') {
                Log::error('Exchange rate API returned error', ['data' => $data]);

                return [
                    'success' => false,
                    'message' => 'API error: '.($data['error-type'] ?? 'Unknown error'),
                ];
            }

            return $this->storeRates($data['conversion_rates']);
        } catch (\Exception $e) {
            Log::error('Exchange rate fetch exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Store fetched rates in the database.
     *
     * @param  array<string, float>  $rates  Currency code => rate to USD
     * @return array{success: bool, message: string, rates_updated: int}
     */
    private function storeRates(array $rates): array
    {
        $fetchedAt = Carbon::now();
        $count = 0;

        foreach ($rates as $currencyCode => $rate) {
            ExchangeRate::updateOrCreate(
                ['currency_code' => $currencyCode],
                [
                    'rate_to_usd' => $rate,
                    'fetched_at' => $fetchedAt,
                ]
            );
            $count++;
        }

        Log::info('Exchange rates refreshed', ['count' => $count]);

        return [
            'success' => true,
            'message' => "Successfully updated {$count} exchange rates.",
            'rates_updated' => $count,
        ];
    }

    /**
     * Get the exchange rate for a currency.
     *
     * @param  string  $currencyCode  ISO 4217 currency code
     * @return float|null Rate to USD, null if not found
     */
    public function getRate(string $currencyCode): ?float
    {
        return ExchangeRate::getRate($currencyCode);
    }

    /**
     * Check if rates need refreshing.
     *
     * @return bool
     */
    public function isStale(): bool
    {
        $hours = config('treasury.exchange_rate_refresh_hours', 24);

        return ExchangeRate::isStale($hours);
    }

    /**
     * Get the last time rates were fetched.
     *
     * @return Carbon|null
     */
    public function getLastFetchedAt(): ?Carbon
    {
        return ExchangeRate::getLastFetchedAt();
    }
}
