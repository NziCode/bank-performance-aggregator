<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/uploads',        [UploadController::class, 'store']);
    Route::get('/uploads/{upload}', [UploadController::class, 'show']);
});
