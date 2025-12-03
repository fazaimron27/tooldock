<?php

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\CampaignController;
use Modules\Newsletter\Http\Controllers\NewsletterDashboardController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Register dashboard route BEFORE resource routes to avoid route conflict
    Route::get('newsletter/dashboard', [NewsletterDashboardController::class, 'index'])->name('newsletter.dashboard');

    Route::resource('newsletter', CampaignController::class)->names([
        'index' => 'newsletter.index',
        'create' => 'newsletter.create',
        'store' => 'newsletter.store',
        'show' => 'newsletter.show',
        'edit' => 'newsletter.edit',
        'update' => 'newsletter.update',
        'destroy' => 'newsletter.destroy',
    ]);

    Route::post('newsletter/{newsletter}/send', [CampaignController::class, 'send'])
        ->name('newsletter.send');
});
