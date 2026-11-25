<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CrashController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

// Laporan crash aplikasi (token opsional).
Route::post('/crash', [CrashController::class, 'store'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
    Route::post('/me/premium-request', [AuthController::class, 'requestPremium'])->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/sync', [SyncController::class, 'sync'])->middleware('throttle:60,1');
});
