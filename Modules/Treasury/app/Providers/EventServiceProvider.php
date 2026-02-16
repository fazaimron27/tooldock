<?php

namespace Modules\Treasury\Providers;

use App\Events\Modules\ModuleInstalled;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Treasury\Listeners\HandleTreasuryInstalled;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\BudgetPeriod;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;
use Modules\Treasury\Observers\BudgetObserver;
use Modules\Treasury\Observers\BudgetPeriodObserver;
use Modules\Treasury\Observers\TransactionObserver;
use Modules\Treasury\Observers\TreasuryGoalObserver;
use Modules\Treasury\Observers\WalletObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ModuleInstalled::class => [
            HandleTreasuryInstalled::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Transaction::observe(TransactionObserver::class);
        BudgetPeriod::observe(BudgetPeriodObserver::class);
        Wallet::observe(WalletObserver::class);
        Budget::observe(BudgetObserver::class);
        TreasuryGoal::observe(TreasuryGoalObserver::class);
    }

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
