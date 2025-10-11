<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class FileRouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerFileMacros();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register route macros for file management
     */
    protected function registerFileMacros(): void
    {
        /**
         * Generic file management routes for any model
         * Usage: Route::fileRoutes('devices', DeviceController::class);
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
         * Usage: Route::apiFileRoutes('devices', DeviceController::class);
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

        /**
         * Resource routes with file management
         * Usage: Route::resourceWithFiles('devices', DeviceController::class);
         */
        Route::macro('resourceWithFiles', function (string $name, string $controller, array $options = []) {
            // Register standard resource routes
            Route::resource($name, $controller, $options);

            // Register file management routes
            Route::fileRoutes($name, $controller);
        });
    }
}
