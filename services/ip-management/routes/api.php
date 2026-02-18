<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\IPController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - IP Management Service
|
| Routes are versioned under /api/v1/* for consistency with gateway.
| All routes follow RESTful conventions.
|
| Version: v1 (current)
*/

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (JWT Authentication Required)
    |--------------------------------------------------------------------------
    |
    | These routes require a valid JWT token.
    | The auth:api middleware validates the JWT and sets up the
    | user context for policy-based authorization.
    |
    */

    Route::middleware(['api', 'stateless.jwt'])->group(function () {
        // IP Management Routes
        Route::apiResource('ip', IPController::class);
        Route::get('ip/{ip}/history', [IPController::class, 'history']);

        /*
         |--------------------------------------------------------------------------
         | Activity Log Routes
         |--------------------------------------------------------------------------
         |
         | Routes for retrieving activity logs from Spatie Activity Log.
         | These are used for the unified audit dashboard.
         |
         */
        Route::get('activity/logs', [ActivityLogController::class, 'index']);
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
            'service' => 'ip-management',
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => 'v1',
        ],
    ]);
});
