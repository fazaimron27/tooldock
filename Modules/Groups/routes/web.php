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
});
