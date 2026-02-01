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
 *
 * @package Gateway
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

    // IP Management Service Routes
    // Wildcard route proxies all IP-related requests to the IP service
    Route::any('ip/{path?}', [IPProxyController::class, 'handle'])
        ->where('path', '.*');
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
