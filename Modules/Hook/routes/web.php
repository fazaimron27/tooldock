<?php

/**
 * Hook Module Web Routes
 *
 * Defines web routes for inbound endpoint CRUD, outbound webhook
 * CRUD, and the send/delivery actions.
 *
 * @author     Tool Dock Team
 * @license    MIT
 *
 * @see RouteServiceProvider::mapWebRoutes()
 */

use Illuminate\Support\Facades\Route;
use Modules\Hook\Http\Controllers\HookController;
use Modules\Hook\Http\Controllers\HookDashboardController;
use Modules\Hook\Http\Controllers\HookInboundController;
use Modules\Hook\Http\Controllers\HookOutboundController;
use Modules\Hook\Providers\RouteServiceProvider;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('hook', [HookController::class, 'index'])
        ->name('hook.index');

    Route::get('hook/dashboard', [HookDashboardController::class, 'index'])
        ->name('hook.dashboard');

    Route::get('hook/inbound/{inbound}', [HookInboundController::class, 'show'])
        ->name('hook.inbound.show');
    Route::post('hook/inbound', [HookInboundController::class, 'store'])
        ->name('hook.inbound.store');
    Route::delete('hook/inbound/{inbound}', [HookInboundController::class, 'destroy'])
        ->name('hook.inbound.destroy');
    Route::put('hook/inbound/{inbound}', [HookInboundController::class, 'update'])
        ->name('hook.inbound.update');
    Route::get('hook/inbound/{inbound}/requests', [HookInboundController::class, 'requests'])
        ->name('hook.inbound.requests');

    Route::get('hook/outbound/{outbound}', [HookOutboundController::class, 'show'])
        ->name('hook.outbound.show');
    Route::post('hook/outbound', [HookOutboundController::class, 'store'])
        ->name('hook.outbound.store');
    Route::put('hook/outbound/{outbound}', [HookOutboundController::class, 'update'])
        ->name('hook.outbound.update');
    Route::delete('hook/outbound/{outbound}', [HookOutboundController::class, 'destroy'])
        ->name('hook.outbound.destroy');
    Route::post('hook/outbound/{outbound}/send', [HookOutboundController::class, 'send'])
        ->name('hook.outbound.send');
    Route::get('hook/outbound/{outbound}/deliveries', [HookOutboundController::class, 'deliveries'])
        ->name('hook.outbound.deliveries');
});
