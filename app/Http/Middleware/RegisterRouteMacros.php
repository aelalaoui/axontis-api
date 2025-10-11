<?php

use Illuminate\Support\Facades\Route;

/**
 * Generic file management routes for any model
 * This macro allows to register file routes for any model dynamically
 */
Route::macro('fileRoutes', function (string $prefix, string $controller, string $parameterName = null) {
    $parameterName = $parameterName ?: Str::singular($prefix);

    Route::prefix($prefix)->name("{$prefix}.")->group(function () use ($controller, $parameterName) {
        // Document management routes
        Route::prefix("{{$parameterName}}/documents")->name('documents.')->group(function () use ($controller, $parameterName) {
            // Get all documents
            Route::get('/', "{$controller}@getDocuments")->name('index');

            // Upload single document
            Route::post('/upload', "{$controller}@uploadDocument")->name('upload');

            // Upload multiple documents
            Route::post('/upload-multiple', "{$controller}@uploadMultipleDocuments")->name('upload-multiple');

            // Delete multiple documents
            Route::delete('/delete-multiple', "{$controller}@deleteMultipleDocuments")->name('delete-multiple');

            // Individual document operations
            Route::prefix('{fileUuid}')->group(function () use ($controller) {
                Route::get('/download', "{$controller}@downloadDocument")->name('download');
                Route::get('/view', "{$controller}@viewDocument")->name('view');
                Route::patch('/rename', "{$controller}@renameDocument")->name('rename');
                Route::delete('/', "{$controller}@deleteDocument")->name('delete');
            });
        });
    });
});

/**
 * Generic file routes helper for API
 */
Route::macro('apiFileRoutes', function (string $prefix, string $controller, string $parameterName = null) {
    $parameterName = $parameterName ?: Str::singular($prefix);

    Route::prefix("api/{$prefix}")->name("api.{$prefix}.")->group(function () use ($controller, $parameterName) {
        Route::prefix("{{$parameterName}}/files")->name('files.')->group(function () use ($controller, $parameterName) {
            Route::get('/', "{$controller}@getDocuments")->name('index');
            Route::post('/', "{$controller}@uploadDocument")->name('store');
            Route::post('/multiple', "{$controller}@uploadMultipleDocuments")->name('store-multiple');
            Route::delete('/multiple', "{$controller}@deleteMultipleDocuments")->name('destroy-multiple');

            Route::prefix('{fileUuid}')->group(function () use ($controller) {
                Route::get('/', "{$controller}@downloadDocument")->name('show');
                Route::patch('/', "{$controller}@renameDocument")->name('update');
                Route::delete('/', "{$controller}@deleteDocument")->name('destroy');
            });
        });
    });
});
