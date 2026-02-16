<?php

/**
 * Refresh Exchange Rates Command
 *
 * Fetches the latest currency exchange rates from the ExchangeRate-API
 * service and stores them locally for use in cross-currency calculations
 * throughout the Treasury module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Console\Commands;

use Illuminate\Console\Command;
use Modules\Treasury\Services\Exchange\ExchangeRateService;

/**
 * Class RefreshExchangeRatesCommand
 *
 * Fetches latest exchange rates from ExchangeRate-API.
 */
class RefreshExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:refresh-rates
                            {--force : Force refresh even if rates are not stale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh exchange rates from ExchangeRate-API';

    /**
     * Execute the console command.
     *
     * @param  ExchangeRateService  $service  The exchange rate service
     * @return int
     */
    public function handle(ExchangeRateService $service): int
    {
        $force = $this->option('force');

        $this->info('Fetching exchange rates from ExchangeRate-API...');

        $result = $service->refreshRates($force);

        if ($result['success']) {
            $this->info($result['message']);

            if (isset($result['rates_updated'])) {
                $this->info("Updated {$result['rates_updated']} currency rates.");
            }

            return Command::SUCCESS;
        }

        $this->error($result['message']);

        return Command::FAILURE;
    }
}
