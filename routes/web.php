<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Zidbih\LiveApi\Http\Controllers\DashboardController;

Route::prefix('liveapi')->middleware('web')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('liveapi.index');
    Route::get('/openapi.json', [DashboardController::class, 'json'])->name('liveapi.json');
});
