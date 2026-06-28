<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // آپلود
    Route::get('/uploads',  [UploadController::class, 'webIndex'])->name('uploads.index');
    Route::post('/uploads', [UploadController::class, 'webStore'])->name('uploads.store');

    // عملکردها
    Route::get('/performances',                              [PerformanceController::class, 'webIndex'])->name('performances.index');
    Route::post('/performances/bulk-approve',               [PerformanceController::class, 'bulkApprove'])->name('performances.bulk-approve');
    Route::post('/performances/bulk-reject',                [PerformanceController::class, 'bulkReject'])->name('performances.bulk-reject');
    Route::post('/performances/{performance}/approve',      [PerformanceController::class, 'approve'])->name('performances.approve');
    Route::post('/performances/{performance}/reject',       [PerformanceController::class, 'reject'])->name('performances.reject');

    // گزارش‌ها
    Route::get('/reports', [ReportController::class, 'webIndex'])->name('reports.index');


    Route::get('/exports/performances', [ExportController::class, 'performances'])->name('exports.performances');
    Route::get('/exports/by-employee',  [ExportController::class, 'byEmployee'])->name('exports.by-employee');
});
