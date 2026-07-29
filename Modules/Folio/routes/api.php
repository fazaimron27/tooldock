<?php

/**
 * Folio Module API Routes
 *
 * API routes for the Folio module (placeholder).
 *
 * @author     Tool Dock Team
 * @license    MIT
 *
 * @see RouteServiceProvider::mapApiRoutes()
 */

use Illuminate\Support\Facades\Route;
use Modules\Folio\Providers\RouteServiceProvider;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {});
