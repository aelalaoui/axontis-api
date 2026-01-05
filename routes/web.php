<?php

use App\Http\Controllers\InstallationController;
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


// Callback route for DocuSign OAuth consent flow
Route::get('/docusign/callback', function () {
    return response()->json([
        'message' => 'Consent granted successfully! You can now close this window and use DocuSign in your application.',
        'status' => 'success'
    ]);
})->name('docusign.callback');

// Signature webhooks viewer
Route::get(
    '/signature/webhooks',
    [\App\Http\Controllers\SignatureController::class, 'viewWebhooks']
)->name('signature.webhooks');

// Client payment route - no middleware
Route::get(
    '/client/{clientUuid}/contract/{contractUuid}/payment',
    [\App\Http\Controllers\ClientController::class, 'payment']
)->name('client.payment');

// Client account creation after payment - no middleware
Route::get(
    '/client/{clientUuid}/contract/{contractUuid}/create-account',
    [\App\Http\Controllers\ClientController::class, 'createAccount']
)->name('client.create-account');

Route::post(
    '/client/create-account',
    [\App\Http\Controllers\ClientController::class, 'storeAccount']
)->name('client.create-account.store');

// Security Routes - Client Space (requires authenticated user with active client status)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'client.active',
])->prefix('client')->name('client.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ClientController::class, 'home'])->name('home');
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

    // CRM Routes
    Route::prefix('crm')->name('crm.')->group(function () {
        // Generic Files Routes - Central file management
        Route::resource('files', \App\Http\Controllers\FileController::class);
        Route::get('files/{file}/download', [\App\Http\Controllers\FileController::class, 'download'])->name('files.download');
        Route::get('files/{file}/view', [\App\Http\Controllers\FileController::class, 'view'])->name('files.view');
        Route::post('files/upload-multiple', [\App\Http\Controllers\FileController::class, 'uploadMultiple'])->name('files.upload-multiple');

        // CRM Suppliers Routes with file management
        Route::resourceWithFiles('suppliers', \App\Http\Controllers\SupplierController::class);
        Route::patch('suppliers/{supplier}/toggle-status', [\App\Http\Controllers\SupplierController::class, 'toggleStatus'])
            ->name('suppliers.toggle-status');

        // CRM Orders Routes with file management
        Route::resourceWithFiles('orders', \App\Http\Controllers\OrderController::class);
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

        // CRM Devices Routes with file management
        Route::resourceWithFiles('devices', \App\Http\Controllers\DeviceController::class);
        Route::patch('devices/{device}/update-stock', [\App\Http\Controllers\DeviceController::class, 'updateStock'])
            ->name('devices.update-stock');

        // CRM Products Routes with file management
        Route::resourceWithFiles('products', \App\Http\Controllers\ProductController::class);

        // API Routes for autocomplete and search
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('suppliers/search', [\App\Http\Controllers\SupplierController::class, 'searchSuppliers'])
                ->name('suppliers.search');
            Route::get('devices/search', [\App\Http\Controllers\DeviceController::class, 'searchDevices'])
                ->name('devices.search');

            // Generic API file routes
            Route::apiFileRoutes('suppliers', \App\Http\Controllers\SupplierController::class);
            Route::apiFileRoutes('devices', \App\Http\Controllers\DeviceController::class);
            Route::apiFileRoutes('products', \App\Http\Controllers\ProductController::class);
            Route::apiFileRoutes('orders', \App\Http\Controllers\OrderController::class);
        });
    });

    // Installation schedule route - protected by client.active
    Route::middleware('client.active')
        ->get('/installation/{uuid}/schedule', [InstallationController::class, 'scheduleView'])
        ->name('installation.schedule');
});
