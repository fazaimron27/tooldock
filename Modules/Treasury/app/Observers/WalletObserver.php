<?php

/**
 * Wallet Observer
 *
 * Observes Wallet model lifecycle events to flush Treasury caches
 * when wallets are created, updated, or deleted.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use Modules\Treasury\Models\Wallet;

/**
 * Class WalletObserver
 *
 * Handles cache invalidation for wallet changes.
 */
class WalletObserver
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Handle the Wallet "created" event.
     *
     * @param  Wallet  $wallet
     * @return void
     */
    public function created(Wallet $wallet): void
    {
        $this->cacheService->flush('treasury', 'WalletObserver');
    }

    /**
     * Handle the Wallet "updated" event.
     *
     * @param  Wallet  $wallet
     * @return void
     */
    public function updated(Wallet $wallet): void
    {
        $this->cacheService->flush('treasury', 'WalletObserver');
    }

    /**
     * Handle the Wallet "deleted" event.
     *
     * @param  Wallet  $wallet
     * @return void
     */
    public function deleted(Wallet $wallet): void
    {
        $this->cacheService->flush('treasury', 'WalletObserver');
    }
}
