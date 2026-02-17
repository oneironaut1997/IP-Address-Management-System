<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\IPController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | API Routes
 |--------------------------------------------------------------------------
 |
 | Here is where you can register API routes for your application. These
 | routes are loaded by the RouteServiceProvider within a group which
 | is assigned the "api" middleware group.
 |
 */

/*
|--------------------------------------------------------------------------
| Protected Routes (Gateway Auth Required)
|--------------------------------------------------------------------------
|
| These routes require valid gateway headers (X-User-ID, X-User-Role).
| The gateway.auth middleware validates the headers and sets up the
| user context for policy-based authorization.
|
*/

Route::middleware(['api', 'gateway.auth'])->group(function () {
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
