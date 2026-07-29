<?php

/**
 * Hook Module API Routes
 *
 * Defines the public webhook receive endpoint accessible
 * without authentication, rate-limited per slug + IP.
 *
 * @author     Tool Dock Team
 * @license    MIT
 *
 * @see RouteServiceProvider::mapApiRoutes()
 */

use Illuminate\Support\Facades\Route;
use Modules\Hook\Http\Controllers\HookInboundController;
use Modules\Hook\Providers\RouteServiceProvider;

Route::middleware(['throttle:hook-catch'])->prefix('v1')->group(function () {
    Route::any('hook/inbound/{slug}', [HookInboundController::class, 'receive'])
        ->name('hook.inbound.receive');
});
