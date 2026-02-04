<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Health checks (tidak perlu auth)
Route::get('/health/live', [HealthController::class, 'live']);
Route::get('/health/ready', [HealthController::class, 'ready']);
Route::get('/health/deep', [HealthController::class, 'deep']);

Route::get('/health/cache-stats', [HealthController::class, 'cacheStats']);
