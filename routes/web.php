<?php

use App\Http\Controllers\Web\ArticleController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Clients (la ressource d'écriture est déclarée d'abord pour que `clients/create` ne soit pas lu comme `clients/{client}`)
    Route::resource('clients', ClientController::class)->except(['index', 'show'])->middleware('can:clients.manage');
    Route::resource('clients', ClientController::class)->only(['index', 'show'])->middleware('can:clients.view');

    // Articles
    Route::resource('articles', ArticleController::class)->only(['index'])->middleware('can:articles.view');
    Route::resource('articles', ArticleController::class)->except(['index', 'show'])->middleware('can:articles.manage');

    // Devis & factures
    Route::middleware('can:documents.export')->get('documents-export', [DocumentController::class, 'export'])->name('documents.export');

    // `create` doit être déclaré avant `{document}` pour ne pas être capturé par show.
    Route::middleware('can:documents.manage')->group(function () {
        Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::post('documents/{document}/convert', [DocumentController::class, 'convert'])->name('documents.convert');
        Route::post('documents/{document}/send', [DocumentController::class, 'send'])->name('documents.send');
    });
    Route::middleware('can:documents.view')->group(function () {
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('documents/{document}/pdf', [DocumentController::class, 'pdf'])->name('documents.pdf');
    });
    Route::patch('documents/{document}/status', [DocumentController::class, 'updateStatus'])
        ->middleware('can:documents.status')->name('documents.status');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])
        ->middleware('can:documents.delete')->name('documents.destroy');

    // Administration
    Route::resource('users', UserController::class)->except('show')->middleware('can:users.manage');
});
