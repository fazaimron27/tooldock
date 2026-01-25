<?php

/**
 * Signal Module Web Routes
 *
 * Web route definitions for the Signal notification module.
 * Provides routes for the notification inbox, viewing, and managing
 * notifications via both Inertia pages and JSON API endpoints.
 *
 * All routes are prefixed with /tooldock via RouteServiceProvider.
 *
 * @author     Tool Dock Team
 * @license    MIT
 *
 * @see \Modules\Signal\Providers\RouteServiceProvider::mapWebRoutes()
 * @see \Modules\Signal\Http\Controllers\SignalController
 */

use Illuminate\Support\Facades\Route;
use Modules\Signal\Http\Controllers\SignalController;

/*
|--------------------------------------------------------------------------
| Signal Module Web Routes
|--------------------------------------------------------------------------
|
| All routes for the Signal notification center module.
| Routes are prefixed with /tooldock via RouteServiceProvider.
| Requires authentication and email verification.
|
| Available Routes:
| - GET  /notifications           - Index page (paginated inbox)
| - GET  /notifications/unread-count - Unread count JSON endpoint
| - GET  /notifications/recent    - Recent notifications JSON for dropdown
| - POST /notifications/read-all  - Mark all as read
| - POST /notifications/bulk-read - Bulk mark selected as read
| - DELETE /notifications/bulk-destroy - Bulk delete selected
| - GET  /notifications/{id}      - Single notification detail page
| - POST /notifications/{id}/read - Mark single as read
| - DELETE /notifications/{id}    - Delete single notification
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Notification index page (inbox view)
    Route::get('notifications', [SignalController::class, 'index'])->name('notifications.index');

    // JSON endpoints for notification bell component
    Route::get('notifications/unread-count', [SignalController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('notifications/recent', [SignalController::class, 'recent'])->name('notifications.recent');

    // Bulk actions
    Route::post('notifications/read-all', [SignalController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/bulk-read', [SignalController::class, 'bulkMarkAsRead'])->name('notifications.bulk-read');
    Route::delete('notifications/bulk-destroy', [SignalController::class, 'bulkDestroy'])->name('notifications.bulk-destroy');

    // Single notification actions
    Route::get('notifications/{notification}', [SignalController::class, 'show'])->name('notifications.show');
    Route::post('notifications/{notification}/read', [SignalController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('notifications/{notification}', [SignalController::class, 'destroy'])->name('notifications.destroy');
});
