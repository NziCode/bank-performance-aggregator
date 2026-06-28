<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ReportController;

// صفحه لاگین
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

// روت‌های محافظت‌شده
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/uploads', [UploadController::class, 'webIndex'])->name('uploads.index');
    Route::post('/uploads', [UploadController::class, 'webStore'])->name('uploads.store');
    Route::get('/performances', [PerformanceController::class, 'webIndex'])->name('performances.index');
    Route::get('/reports', [ReportController::class, 'webIndex'])->name('reports.index');
});
