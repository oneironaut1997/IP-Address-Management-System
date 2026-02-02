<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['prefix' => 'auth'], function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    // Protected routes
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Audit Log Routes
|--------------------------------------------------------------------------
|
| Routes for retrieving audit logs. These require authentication and
| are typically accessed by admin users for compliance purposes.
|
*/

Route::group(['prefix' => 'audit', 'middleware' => 'auth:api'], function () {
    Route::get('logs', [AuditController::class, 'index']);
    Route::get('logs/{id}', [AuditController::class, 'show']);
    Route::get('event-types', [AuditController::class, 'eventTypes']);
});
