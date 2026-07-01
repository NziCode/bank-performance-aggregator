<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\PerformanceCardExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/admin'));

Route::middleware('auth')->group(function () {
    // خروجی Excel گزارش‌های قبلی
    Route::get('/exports/performances', [ExportController::class, 'performances'])->name('exports.performances');
    Route::get('/exports/by-employee',  [ExportController::class, 'byEmployee'])->name('exports.by-employee');

    // خروجی کارنامه عملکرد (Excel و PDF)
    Route::get('/reports/performance-card/export', PerformanceCardExportController::class)
        ->name('reports.performance-card.export');
});
