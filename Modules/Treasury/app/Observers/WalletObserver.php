<?php

namespace Modules\Treasury\Observers;

use App\Services\Cache\CacheService;
use Modules\Treasury\Models\Wallet;

class WalletObserver
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    public function created(Wallet $wallet): void
    {
        $this->cacheService->flush('treasury', 'WalletObserver');
    }

    public function updated(Wallet $wallet): void
    {
        $this->cacheService->flush('treasury', 'WalletObserver');
    }

    public function deleted(Wallet $wallet): void
    {
        $this->cacheService->flush('treasury', 'WalletObserver');
    }
}
