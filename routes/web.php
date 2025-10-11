<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Axontis CRM Dashboard
    Route::get('/crm', function () {
        return Inertia::render('AxontisDashboard');
    })->name('crm.dashboard');

    // CRM Suppliers Routes
    Route::prefix('crm')->name('crm.')->group(function () {
        // Files Routes - moved to CRM
        Route::prefix('files')->name('files.')->group(function () {
            Route::get('/', [\App\Http\Controllers\FileController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\FileController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\FileController::class, 'store'])->name('store');
            Route::get('/{file}', [\App\Http\Controllers\FileController::class, 'show'])->name('show');
            Route::get('/{file}/edit', [\App\Http\Controllers\FileController::class, 'edit'])->name('edit');
            Route::put('/{file}', [\App\Http\Controllers\FileController::class, 'update'])->name('update');
            Route::delete('/{file}', [\App\Http\Controllers\FileController::class, 'destroy'])->name('destroy');
            Route::get('/{file}/download', [\App\Http\Controllers\FileController::class, 'download'])->name('download');
            Route::get('/{file}/view', [\App\Http\Controllers\FileController::class, 'view'])->name('view');
            Route::post('/upload-multiple', [\App\Http\Controllers\FileController::class, 'uploadMultiple'])->name('upload-multiple');
        });

        // CRM Suppliers Routes
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
        Route::patch('suppliers/{supplier}/toggle-status', [\App\Http\Controllers\SupplierController::class, 'toggleStatus'])
            ->name('suppliers.toggle-status');

        // CRM Orders Routes
        Route::resource('orders', \App\Http\Controllers\OrderController::class);
        Route::patch('orders/{order}/approve', [\App\Http\Controllers\OrderController::class, 'approve'])
            ->name('orders.approve');
        Route::patch('orders/{order}/mark-as-ordered', [\App\Http\Controllers\OrderController::class, 'markAsOrdered'])
            ->name('orders.mark-as-ordered');
        Route::patch('orders/{order}/cancel', [\App\Http\Controllers\OrderController::class, 'cancel'])
            ->name('orders.cancel');

        // Order Arrivals Routes
        Route::post('orders/{order}/arrivals/process', [\App\Http\Controllers\OrderArrivalController::class, 'processArrival'])
            ->name('orders.arrivals.process');
        Route::get('orders/{order}/arrivals/data', [\App\Http\Controllers\OrderArrivalController::class, 'getArrivalData'])
            ->name('orders.arrivals.data');

        // CRM Devices Routes
        Route::resource('devices', \App\Http\Controllers\DeviceController::class);
        Route::patch('devices/{device}/update-stock', [\App\Http\Controllers\DeviceController::class, 'updateStock'])
            ->name('devices.update-stock');

        // CRM Products Routes
        Route::resource('products', \App\Http\Controllers\ProductController::class);

        // API Routes for autocomplete
        Route::get('api/suppliers/search', [\App\Http\Controllers\SupplierController::class, 'searchSuppliers'])
            ->name('api.suppliers.search');
        Route::get('api/devices/search', [\App\Http\Controllers\DeviceController::class, 'searchDevices'])
            ->name('api.devices.search');
    });
});
