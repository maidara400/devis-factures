<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('users', UserController::class)->middleware('can:users.manage');

        Route::apiResource('clients', ClientController::class)->only(['index', 'show'])->middleware('can:clients.view');
        Route::apiResource('clients', ClientController::class)->except(['index', 'show'])->middleware('can:clients.manage');

        Route::apiResource('articles', ArticleController::class)->only(['index', 'show'])->middleware('can:articles.view');
        Route::apiResource('articles', ArticleController::class)->except(['index', 'show'])->middleware('can:articles.manage');
    });
});
