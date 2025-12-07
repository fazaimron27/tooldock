<?php

use Illuminate\Support\Facades\Route;
use Modules\Groups\Http\Controllers\GroupMemberController;
use Modules\Groups\Http\Controllers\GroupsController;
use Modules\Groups\Http\Controllers\GroupsDashboardController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard route must be before resource routes to avoid route conflicts
    Route::get('groups/dashboard', [GroupsDashboardController::class, 'index'])->name('groups.dashboard');

    Route::resource('groups', GroupsController::class)->names([
        'index' => 'groups.groups.index',
        'create' => 'groups.groups.create',
        'store' => 'groups.groups.store',
        'show' => 'groups.groups.show',
        'edit' => 'groups.groups.edit',
        'update' => 'groups.groups.update',
        'destroy' => 'groups.groups.destroy',
    ]);

    // Member management routes
    Route::post('groups/{group}/transfer-members', [GroupMemberController::class, 'transferMembers'])
        ->name('groups.transfer-members');

    Route::post('groups/{group}/add-members', [GroupMemberController::class, 'addMembers'])
        ->name('groups.add-members');

    Route::post('groups/{group}/remove-members', [GroupMemberController::class, 'removeMembers'])
        ->name('groups.remove-members');

    Route::get('groups/{group}/members', [GroupMemberController::class, 'members'])
        ->name('groups.members');

    Route::get('groups/{group}/available-users', [GroupMemberController::class, 'availableUsers'])
        ->name('groups.available-users');
});
