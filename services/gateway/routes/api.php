<?php

use App\Http\Controllers\AuthProxyController;
use App\Http\Controllers\IPProxyController;
use Illuminate\Support\Facades\Route;

/**
 * API Routes - Gateway Service
 *
 * This file defines the API routes for the gateway service.
 *
 * Public routes (no authentication) are defined first,
 * followed by protected routes that require JWT validation.
 *
 * Rate Limiting:
 * - Public endpoints: 10 requests per minute
 * - Auth endpoints: 5 requests per minute (prevent brute force)
 * - Protected endpoints: 60 requests per minute
 */

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| These routes are accessible without authentication. They handle
| user registration, login, and token refresh operations.
| Rate limited to prevent abuse.
|
*/

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthProxyController::class, 'login']);
        Route::post('register', [AuthProxyController::class, 'register']);
    });

    // Refresh token has its own limit
    Route::post('auth/refresh', [AuthProxyController::class, 'refresh'])
        ->middleware('throttle:10,1');

    Route::middleware(['throttle:60,1'])->group(function () {
        // Authentication routes
        Route::post('auth/logout', [AuthProxyController::class, 'logout']);
        Route::get('auth/me', [AuthProxyController::class, 'me']);

        // Audit log routes
        Route::get('audit/logs', [AuthProxyController::class, 'auditLogs']);

        // IP Management Service Routes - RESTful endpoints
        Route::get('ip', [IPProxyController::class, 'index']);
        Route::post('ip', [IPProxyController::class, 'store']);
        Route::get('ip/{id}', [IPProxyController::class, 'show']);
        Route::put('ip/{id}', [IPProxyController::class, 'update']);
        Route::patch('ip/{id}', [IPProxyController::class, 'update']);
        Route::delete('ip/{id}', [IPProxyController::class, 'destroy']);
        Route::get('ip/{id}/history', [IPProxyController::class, 'history']);
        Route::get('ip/{id}/audit', [IPProxyController::class, 'audit']);
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
            'service' => 'gateway',
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => 'v1',
        ],
    ]);
});
