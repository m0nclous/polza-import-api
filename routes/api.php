<?php

use App\Http\Controllers\Import\ImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('import')->group(function () {
    Route::prefix('groups')->group(function () {
        Route::get('/sync', [ImportController::class, 'syncGroups']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/sync', [ImportController::class, 'syncProducts']);
    });
});
