<?php

use Illuminate\Support\Facades\Route;
use Modules\Groups\Http\Controllers\Api\GroupsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('groups', GroupsController::class)->names('groups');

    // Bulk operations
    Route::post('groups/bulk/assign-users', [GroupsController::class, 'bulkAssignUsers'])
        ->name('groups.bulk.assign-users');
    Route::post('groups/bulk/remove-users', [GroupsController::class, 'bulkRemoveUsers'])
        ->name('groups.bulk.remove-users');
});
