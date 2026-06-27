<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // احراز هویت
    Route::post('/login',  [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // آپلود
        Route::post('/uploads',         [UploadController::class, 'store']);
        Route::get('/uploads/{upload}', [UploadController::class, 'show']);
    });

});
