<?php

namespace Modules\Treasury\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Services\Exchange\ExchangeRateService;

/**
 * Job to refresh exchange rates asynchronously.
 *
 * Captures the API key at dispatch time to avoid queue worker
 * environment caching issues.
 */
class RefreshExchangeRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $apiKey,
        private bool $force = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ExchangeRateService $service): void
    {
        try {
            // Temporarily set the API key in config for this job
            config(['treasury.exchange_rate_api_key' => $this->apiKey]);

            $result = $service->refreshRates($this->force);

            if ($result['success']) {
                Log::info('RefreshExchangeRatesJob: Exchange rates fetched successfully.', [
                    'rates_updated' => $result['rates_updated'] ?? 0,
                ]);
            } else {
                Log::warning('RefreshExchangeRatesJob: Failed to fetch exchange rates.', [
                    'message' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('RefreshExchangeRatesJob: Error fetching exchange rates.', [
                'error' => $e->getMessage(),
            ]);

            throw $e; // Rethrow to trigger retry
        }
    }
}
