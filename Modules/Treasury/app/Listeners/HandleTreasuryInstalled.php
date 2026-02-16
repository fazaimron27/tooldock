<?php

namespace Modules\Treasury\Listeners;

use App\Events\Modules\ModuleInstalled;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Jobs\RefreshExchangeRatesJob;

/**
 * Listener to handle Treasury module installation.
 *
 * Automatically fetches exchange rates when the Treasury module is installed,
 * ensuring multi-currency features work correctly from the start.
 */
class HandleTreasuryInstalled
{
    /**
     * Handle the event.
     */
    public function handle(ModuleInstalled $event): void
    {
        // Only handle Treasury module installation
        if ($event->moduleName !== 'Treasury') {
            return;
        }

        $apiKey = config('treasury.exchange_rate_api_key');

        if (empty($apiKey)) {
            Log::warning('Treasury module installed: Skipping exchange rates fetch - API key not configured.');

            return;
        }

        Log::info('Treasury module installed: Scheduling exchange rates fetch...');

        // Dispatch job with API key captured at dispatch time
        // This avoids queue worker environment caching issues
        RefreshExchangeRatesJob::dispatch($apiKey, force: true)
            ->delay(now()->addSeconds(5));
    }
}
