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
});
