<?php

use Illuminate\Support\Facades\Route;
use Modules\Treasury\Http\Controllers\BudgetController;
use Modules\Treasury\Http\Controllers\TransactionController;
use Modules\Treasury\Http\Controllers\TreasuryDashboardController;
use Modules\Treasury\Http\Controllers\TreasuryGoalController;
use Modules\Treasury\Http\Controllers\TreasuryOverviewController;
use Modules\Treasury\Http\Controllers\TreasuryReportController;
use Modules\Treasury\Http\Controllers\WalletController;

Route::middleware(['auth', 'verified'])->prefix('treasury')->name('treasury.')->group(function () {
    // Index (main landing page)
    Route::get('/', [TreasuryOverviewController::class, 'index'])->name('index');

    // Dashboard (widget-based dashboard)
    Route::get('/dashboard', [TreasuryDashboardController::class, 'index'])->name('dashboard');

    // Reports
    Route::get('/reports', [TreasuryReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [TreasuryReportController::class, 'export'])->name('reports.export');

    // Wallets
    Route::resource('wallets', WalletController::class);

    // Goals
    Route::post('goals/{goal}/allocate', [TreasuryGoalController::class, 'allocate'])->name('goals.allocate');
    Route::resource('goals', TreasuryGoalController::class);

    // Budgets
    Route::resource('budgets', BudgetController::class)->except(['show']);

    // Transactions
    Route::resource('transactions', TransactionController::class);

    // Financial Health (JSON endpoints for AJAX calls)
    Route::prefix('financial-health')->name('financial-health.')->group(function () {
        Route::get('goal-recommendation', [\Modules\Treasury\Http\Controllers\Api\FinancialHealthController::class, 'getGoalRecommendation'])
            ->name('goal-recommendation');
        Route::get('data-status', [\Modules\Treasury\Http\Controllers\Api\FinancialHealthController::class, 'getDataStatus'])
            ->name('data-status');
    });
});
