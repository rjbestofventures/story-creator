<?php

use App\Http\Controllers\Api\ProvisionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\ProvisionToken;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'store']);
});

Route::middleware(ProvisionToken::class)->prefix('provision')->group(function () {
    Route::post('/user', [ProvisionController::class, 'createUser']);
    Route::post('/verify-partner', [ProvisionController::class, 'verifyPartner']);
    Route::post('/convert-to-partner', [ProvisionController::class, 'convertToPartner']);
    Route::post('/deactivate', [ProvisionController::class, 'deactivateAccount']);
});
