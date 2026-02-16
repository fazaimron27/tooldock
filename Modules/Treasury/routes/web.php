<?php

/**
 * Treasury Module Web Routes
 *
 * Web route definitions for the Treasury module including CRUD resources
 * for wallets, transactions, budgets, and goals. Also registers
 * financial health API endpoints under the web middleware.
 *
 * All routes are prefixed with /treasury via RouteServiceProvider.
 *
 * @author     Tool Dock Team
 * @license    MIT
 *
 * @see \Modules\Treasury\Providers\RouteServiceProvider::mapWebRoutes()
 */

use Illuminate\Support\Facades\Route;
use Modules\Treasury\Http\Controllers\BudgetController;
use Modules\Treasury\Http\Controllers\TransactionController;
use Modules\Treasury\Http\Controllers\TreasuryDashboardController;
use Modules\Treasury\Http\Controllers\TreasuryGoalController;
use Modules\Treasury\Http\Controllers\TreasuryOverviewController;
use Modules\Treasury\Http\Controllers\TreasuryReportController;
use Modules\Treasury\Http\Controllers\WalletController;

Route::middleware(['auth', 'verified'])->prefix('treasury')->name('treasury.')->group(function () {
    Route::get('/', [TreasuryOverviewController::class, 'index'])->name('index');
    Route::get('/dashboard', [TreasuryDashboardController::class, 'index'])->name('dashboard');

    Route::get('/reports', [TreasuryReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [TreasuryReportController::class, 'export'])->name('reports.export');

    Route::resource('wallets', WalletController::class);

    Route::post('goals/{goal}/allocate', [TreasuryGoalController::class, 'allocate'])->name('goals.allocate');
    Route::resource('goals', TreasuryGoalController::class);

    Route::resource('budgets', BudgetController::class)->except(['show']);

    Route::resource('transactions', TransactionController::class);

    Route::prefix('financial-health')->name('financial-health.')->group(function () {
        Route::get('goal-recommendation', [\Modules\Treasury\Http\Controllers\Api\FinancialHealthController::class, 'getGoalRecommendation'])
            ->name('goal-recommendation');
        Route::get('data-status', [\Modules\Treasury\Http\Controllers\Api\FinancialHealthController::class, 'getDataStatus'])
            ->name('data-status');
    });
});
