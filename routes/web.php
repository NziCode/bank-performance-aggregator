<?php

use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/admin'));

// خروجی Excel
Route::middleware('auth')->group(function () {
    Route::get('/exports/performances', [ExportController::class, 'performances'])->name('exports.performances');
    Route::get('/exports/by-employee',  [ExportController::class, 'byEmployee'])->name('exports.by-employee');
});
