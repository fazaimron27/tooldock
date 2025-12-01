<?php

use Illuminate\Support\Facades\Route;
use Modules\AuditLog\Http\Controllers\AuditLogController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('auditlog.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('auditlog.show');
    Route::get('audit-logs/export/csv', [AuditLogController::class, 'export'])->name('auditlog.export');
});
