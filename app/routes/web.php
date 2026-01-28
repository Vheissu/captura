<?php

use App\Http\Controllers\DocsController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', DocsController::class);
Route::get('/docs', DocsController::class);
Route::get('/health', HealthController::class);
