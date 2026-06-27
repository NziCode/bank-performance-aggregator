<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // احراز هویت
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // آپلود
        Route::post('/uploads',         [UploadController::class, 'store']);
        Route::get('/uploads/{upload}', [UploadController::class, 'show']);

        // عملکردها
        Route::get('/performances',              [PerformanceController::class, 'index']);
        Route::post('/performances/{performance}/approve', [PerformanceController::class, 'approve']);
        Route::post('/performances/{performance}/reject',  [PerformanceController::class, 'reject']);
        Route::post('/performances/bulk-approve', [PerformanceController::class, 'bulkApprove']);
        Route::post('/performances/bulk-reject',  [PerformanceController::class, 'bulkReject']);
        Route::get('/rejection-reasons',          [PerformanceController::class, 'rejectionReasons']);

        // گزارش‌ها
        Route::get('/reports/summary',     [ReportController::class, 'summary']);
        Route::get('/reports/by-employee', [ReportController::class, 'byEmployee']);
        Route::get('/reports/by-branch',   [ReportController::class, 'byBranch']);

    });

});
