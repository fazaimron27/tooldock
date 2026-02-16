<?php

/**
 * Auth Service Provider
 *
 * Registers model-policy mappings for Treasury module authorization.
 * Maps Budget, Transaction, TreasuryGoal, and Wallet models to their
 * respective policy classes for permission-based access control.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Treasury\Models\Budget;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;
use Modules\Treasury\Policies\BudgetPolicy;
use Modules\Treasury\Policies\TransactionPolicy;
use Modules\Treasury\Policies\TreasuryGoalPolicy;
use Modules\Treasury\Policies\WalletPolicy;

/**
 * Class AuthServiceProvider
 *
 * Treasury module authentication and authorization service provider.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Wallet::class => WalletPolicy::class,
        TreasuryGoal::class => TreasuryGoalPolicy::class,
        Budget::class => BudgetPolicy::class,
        Transaction::class => TransactionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
