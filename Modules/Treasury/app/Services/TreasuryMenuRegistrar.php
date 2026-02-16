<?php

/**
 * Treasury Menu Registrar
 *
 * Registers sidebar navigation menu items for the Treasury module
 * including wallets, goals, budgets, transactions, and reports.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the Treasury module.
 */
class TreasuryMenuRegistrar
{
    /**
     * Register all menu items for the Treasury module.
     *
     * @param  MenuRegistry  $menuRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'Life OS',
            label: 'Treasury',
            route: 'treasury.index',
            icon: 'Landmark',
            order: 10,
            permission: 'treasuries.treasury.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Life OS',
            label: 'Wallets',
            route: 'treasury.wallets.index',
            icon: 'Wallet',
            order: 10,
            permission: 'treasuries.wallet.view',
            parentKey: 'treasury.index',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Life OS',
            label: 'Goals',
            route: 'treasury.goals.index',
            icon: 'Target',
            order: 20,
            permission: 'treasuries.goal.view',
            parentKey: 'treasury.index',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Life OS',
            label: 'Budgets',
            route: 'treasury.budgets.index',
            icon: 'PieChart',
            order: 30,
            permission: 'treasuries.budget.view',
            parentKey: 'treasury.index',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Life OS',
            label: 'Transactions',
            route: 'treasury.transactions.index',
            icon: 'History',
            order: 40,
            permission: 'treasuries.transaction.view',
            parentKey: 'treasury.index',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Life OS',
            label: 'Reports',
            route: 'treasury.reports',
            icon: 'TableProperties',
            order: 50,
            permission: 'treasuries.report.view',
            parentKey: 'treasury.index',
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Treasury Dashboard',
            route: 'treasury.dashboard',
            icon: 'LayoutDashboard',
            order: 70,
            permission: 'treasuries.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}
