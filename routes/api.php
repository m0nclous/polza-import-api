<?php

use App\Http\Controllers\Import\CacheController;
use App\Http\Controllers\Import\CacheImageController;
use App\Http\Controllers\Import\ImagesController;
use App\Http\Controllers\Import\ImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('import')->group(function () {
    Route::prefix('cache')->group(function () {
       Route::prefix('products')->group(function () {
           Route::get('/', [CacheController::class, 'get']);
           Route::patch('/', [CacheController::class, 'set']);
           Route::delete('/', [CacheController::class, 'forget']);
       });

        Route::prefix('images')->group(function () {
            Route::get('/', [CacheImageController::class, 'get']);
            Route::patch('/', [CacheImageController::class, 'set']);
            Route::delete('/', [CacheImageController::class, 'forget']);
        });
    });

    Route::prefix('groups')->group(function () {
        Route::get('/sync', [ImportController::class, 'groupsSync']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/sync', [ImportController::class, 'productsSync']);
    });

    Route::prefix('images')->group(function () {
        Route::get('/insert', [ImportController::class, 'imageInsert']);
        Route::get('/products/sync', [ImagesController::class, 'productsSync']);
    });
});
