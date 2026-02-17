<?php

use App\Http\Controllers\AuthProxyController;
use App\Http\Controllers\IPProxyController;
use Illuminate\Support\Facades\Route;

/**
 * API Routes - Gateway Service
 *
 * This file defines the API routes for the gateway service.
 * Public routes (no authentication) are defined first,
 * followed by protected routes that require JWT validation.
 */

/*
|--------------------------------------------------------------------------
| Public Routes (No JWT Required)
|--------------------------------------------------------------------------
|
| These routes are accessible without authentication. They handle
| user registration, login, and token refresh operations.
|
*/

Route::post('auth/login', [AuthProxyController::class, 'login']);
Route::post('auth/register', [AuthProxyController::class, 'register']);
Route::post('auth/refresh', [AuthProxyController::class, 'refresh']);

/*
|--------------------------------------------------------------------------
| Protected Routes (JWT Required)
|--------------------------------------------------------------------------
|
| These routes require a valid JWT token. The 'jwt' middleware validates
| the token and forwards user context (X-User-ID, X-User-Role) to
| backend services.
|
*/

Route::middleware('jwt')->group(function () {
    // Authentication routes that require valid token
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
        ],
    ]);
});
