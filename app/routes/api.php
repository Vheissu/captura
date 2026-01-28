<?php

declare(strict_types=1);

use App\Http\Controllers\ScreenshotController;
use App\Http\Middleware\JsonResponse;
use App\Http\Middleware\OptionalApiKey;
use App\Http\Middleware\RateLimit;
use Illuminate\Support\Facades\Route;

Route::middleware([JsonResponse::class, OptionalApiKey::class, RateLimit::class])->group(function () {
    Route::get('/screenshot', [ScreenshotController::class, 'capture']);
    Route::post('/screenshot', [ScreenshotController::class, 'capture']);
    Route::post('/screenshot/async', [ScreenshotController::class, 'async']);
    Route::post('/screenshot/bulk', [ScreenshotController::class, 'bulk']);
    Route::get('/screenshot/{id}', [ScreenshotController::class, 'show'])->name('screenshot.show');
});
