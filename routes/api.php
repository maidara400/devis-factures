<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('users', UserController::class)->middleware('can:users.manage');

        Route::apiResource('clients', ClientController::class)->only(['index', 'show'])->middleware('can:clients.view');
        Route::apiResource('clients', ClientController::class)->except(['index', 'show'])->middleware('can:clients.manage');

        Route::apiResource('articles', ArticleController::class)->only(['index', 'show'])->middleware('can:articles.view');
        Route::apiResource('articles', ArticleController::class)->except(['index', 'show'])->middleware('can:articles.manage');

        Route::middleware('can:documents.export')->group(function () {
            Route::get('documents-export', [ExportController::class, 'stream']);
            Route::post('exports', [ExportController::class, 'store']);
            Route::get('exports/{export}', [ExportController::class, 'show']);
            Route::get('exports/{export}/download', [ExportController::class, 'download']);
        });

        Route::middleware('can:documents.view')->group(function () {
            Route::get('documents', [DocumentController::class, 'index']);
            Route::get('documents/{document}', [DocumentController::class, 'show']);
            Route::get('documents/{document}/pdf', [DocumentController::class, 'pdf']);
        });
        Route::middleware('can:documents.manage')->group(function () {
            Route::post('documents', [DocumentController::class, 'store']);
            Route::put('documents/{document}', [DocumentController::class, 'update']);
            Route::post('documents/{document}/convert-to-invoice', [DocumentController::class, 'convertToInvoice']);
            Route::post('documents/{document}/send', [DocumentController::class, 'send']);
        });
        Route::patch('documents/{document}/status', [DocumentController::class, 'updateStatus'])->middleware('can:documents.status');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->middleware('can:documents.delete');
    });
});
