<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplianceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoyaltyController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [AuthController::class, 'token'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', DashboardController::class);

    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/{location}', [LocationController::class, 'show']);
    Route::post('/locations', [LocationController::class, 'store'])->middleware('role:franchisor');

    Route::get('/royalties', [RoyaltyController::class, 'index']);
    Route::post('/royalties/calculate', [RoyaltyController::class, 'calculate']);
    Route::post('/royalties/{statement}/paid', [RoyaltyController::class, 'markPaid'])
        ->middleware('role:franchisor,regional_manager');

    Route::get('/compliance', [ComplianceController::class, 'index']);
    Route::put('/compliance/{check}', [ComplianceController::class, 'update']);

    Route::get('/locations/{location}/financials', [ReportController::class, 'financials']);
});
