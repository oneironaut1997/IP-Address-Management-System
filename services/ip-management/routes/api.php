<?php

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

// IP Management Routes
// Note: Authentication is handled by the Gateway service
// X-User-ID and X-User-Role headers are passed from Gateway
Route::middleware('api')->group(function () {
    Route::apiResource('ip', IPController::class);
    Route::get('ip/{ip}/history', [IPController::class, 'history']);
});
