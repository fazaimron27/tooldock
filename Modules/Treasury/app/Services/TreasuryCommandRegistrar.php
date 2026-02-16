<?php

namespace Modules\Treasury\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Treasury module.
 */
class TreasuryCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Treasury module.
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        // Treasury navigation commands
        $registry->registerMany($moduleName, 'Life OS', [
            [
                'label' => 'Wallets',
                'route' => 'treasury.wallets.index',
                'icon' => 'wallet',
                'parent' => 'Treasury',
                'permission' => 'treasuries.wallet.view',
                'keywords' => ['wallet', 'accounts', 'money', 'balance'],
                'order' => 10,
            ],
            [
                'label' => 'Goals',
                'route' => 'treasury.goals.index',
                'icon' => 'target',
                'parent' => 'Treasury',
                'permission' => 'treasuries.goal.view',
                'keywords' => ['goal', 'savings', 'target', 'plan'],
                'order' => 20,
            ],
            [
                'label' => 'Budgets',
                'route' => 'treasury.budgets.index',
                'icon' => 'pie-chart',
                'parent' => 'Treasury',
                'permission' => 'treasuries.budget.view',
                'keywords' => ['budget', 'spending', 'limit', 'expense'],
                'order' => 30,
            ],
            [
                'label' => 'Transactions',
                'route' => 'treasury.transactions.index',
                'icon' => 'history',
                'parent' => 'Treasury',
                'permission' => 'treasuries.transaction.view',
                'keywords' => ['transaction', 'payment', 'transfer', 'history'],
                'order' => 40,
            ],
            [
                'label' => 'Reports',
                'route' => 'treasury.reports.index',
                'icon' => 'table-properties',
                'parent' => 'Treasury',
                'permission' => 'treasuries.report.view',
                'keywords' => ['report', 'analytics', 'summary', 'chart'],
                'order' => 50,
            ],
        ]);

        // Quick actions
        $registry->registerMany($moduleName, 'Quick Actions', [
            [
                'label' => 'Create Transaction',
                'route' => 'treasury.transactions.create',
                'icon' => 'plus',
                'permission' => 'treasuries.transaction.create',
                'keywords' => ['new', 'add', 'transaction', 'expense', 'income'],
                'order' => 10,
            ],
        ]);
    }
}
