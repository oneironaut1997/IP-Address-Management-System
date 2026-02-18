<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Auth Service
|
| All routes follow RESTful conventions.
|
| Public routes (no authentication) are defined first,
| followed by protected routes that require JWT validation.
|
| Rate Limiting:
| - Auth endpoints: 5 requests per minute (prevent brute force)
|
*/

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (JWT Required)
    |--------------------------------------------------------------------------
    |
    | These routes require a valid JWT token.
    |
    */

    Route::prefix('auth')->middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    /*
    |--------------------------------------------------------------------------
    | Activity Log Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('activity')->middleware('auth:api')->group(function () {
        Route::get('logs', [ActivityLogController::class, 'index']);
    });
});

/*
|--------------------------------------------------------------------------
| Health Check Route
|--------------------------------------------------------------------------
|
| Simple health check endpoint for monitoring.
|
*/

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'service' => 'auth-service',
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => 'v1',
        ],
    ]);
});
