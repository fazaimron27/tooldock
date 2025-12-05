<?php

use Illuminate\Support\Facades\Route;
use Modules\Groups\Http\Controllers\GroupsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('groups', GroupsController::class)->names([
        'index' => 'groups.groups.index',
        'create' => 'groups.groups.create',
        'store' => 'groups.groups.store',
        'show' => 'groups.groups.show',
        'edit' => 'groups.groups.edit',
        'update' => 'groups.groups.update',
        'destroy' => 'groups.groups.destroy',
    ]);

    Route::post('groups/{group}/transfer-members', [GroupsController::class, 'transferMembers'])
        ->name('groups.transfer-members');

    Route::post('groups/{group}/add-members', [GroupsController::class, 'addMembers'])
        ->name('groups.add-members');

    Route::post('groups/{group}/remove-members', [GroupsController::class, 'removeMembers'])
        ->name('groups.remove-members');
});
