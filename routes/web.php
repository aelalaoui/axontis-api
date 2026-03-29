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

// User invitation password setup - no middleware (signed URL)
Route::get(
    '/user/setup-password',
    [\App\Http\Controllers\UserController::class, 'showSetupPassword']
)->name('user.setup-password')->middleware('signed');

Route::post(
    '/user/setup-password',
    [\App\Http\Controllers\UserController::class, 'storePassword']
)->name('user.store-password');


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

        // CRM Users Routes - Only accessible by managers and administrators
        Route::middleware('role:manager,administrator')->group(function () {
            Route::resource('users', \App\Http\Controllers\UserController::class)->except(['destroy']);
            Route::patch('users/{user}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus'])
                ->name('users.toggle-status');
            Route::post('users/{user}/resend-invitation', [\App\Http\Controllers\UserController::class, 'resendInvitation'])
                ->name('users.resend-invitation');

            // CRM Suppliers Routes - Only accessible by managers and administrators
            Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
            Route::patch('suppliers/{supplier}/toggle-status', [\App\Http\Controllers\SupplierController::class, 'toggleStatus'])
                ->name('suppliers.toggle-status');
        });

        // CRM Tasks — accessibles à tous les rôles internes (chacun filtre ses propres tâches)
        Route::middleware('role:manager,administrator,operator,technician,accountant,storekeeper')->group(function () {
            // CRM Installations Routes
            Route::get('installations/{uuid}', [\App\Http\Controllers\CrmInstallationController::class, 'show'])
                ->name('installations.show');
            Route::post('installations/{uuid}/alarm-devices/{deviceUuid}/test-heartbeat', [\App\Http\Controllers\CrmInstallationController::class, 'testHeartbeat'])
                ->name('installations.alarm-devices.test-heartbeat');

            // CRM Tasks Routes
            Route::get('tasks', [\App\Http\Controllers\TaskController::class, 'index'])
                ->name('tasks.index');
            Route::get('tasks/{uuid}', [\App\Http\Controllers\TaskController::class, 'show'])
                ->name('tasks.show');
            Route::patch('tasks/{uuid}/assign-technician', [\App\Http\Controllers\TaskController::class, 'assignTechnician'])
                ->name('tasks.assign-technician');
            Route::patch('tasks/{uuid}/assign-postal', [\App\Http\Controllers\TaskController::class, 'assignPostal'])
                ->name('tasks.assign-postal');

            // Routes réservées aux managers et administrateurs
            Route::middleware('role:manager,administrator')->group(function () {
                Route::patch('tasks/{uuid}/reassign-technician', [\App\Http\Controllers\TaskController::class, 'reassignTechnician'])
                    ->name('tasks.reassign-technician');
                Route::patch('tasks/{uuid}/device/{deviceUuid}/serial', [\App\Http\Controllers\TaskController::class, 'updateDeviceSerial'])
                    ->name('tasks.device-serial');
            });

            Route::get('clients', [\App\Http\Controllers\ClientController::class, 'index'])
                ->name('clients.index');
            Route::get('clients/{uuid}', [\App\Http\Controllers\ClientController::class, 'show'])
                ->name('clients.show');
            Route::get('clients/{uuid}/edit', [\App\Http\Controllers\ClientController::class, 'edit'])
                ->name('clients.edit');
            Route::put('clients/{uuid}', [\App\Http\Controllers\ClientController::class, 'updateCRM'])
                ->name('clients.update');
            Route::patch('clients/{uuid}/toggle-status', [\App\Http\Controllers\ClientController::class, 'toggleStatus'])
                ->name('clients.toggle-status');
            // CRM Contracts Routes - Only for managers, administrators, and operators
            Route::get('contracts', [\App\Http\Controllers\ContractController::class, 'crmIndex'])
                ->name('contracts.index');
            Route::get('contracts/{uuid}', [\App\Http\Controllers\ContractController::class, 'crmShow'])
                ->name('contracts.show');
            Route::get('contracts/{uuid}/edit', [\App\Http\Controllers\ContractController::class, 'edit'])
                ->name('contracts.edit');
            Route::put('contracts/{uuid}', [\App\Http\Controllers\ContractController::class, 'update'])
                ->name('contracts.update');

            // Installation assignment
            Route::post(
                'installations/{uuid}/assign',
                [\App\Http\Controllers\InstallationAssignmentController::class, 'assign']
            )->name('installations.assign');
        });

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

        // CRM Communications Routes - Traçabilité des notifications
        Route::prefix('communications')->name('communications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\CommunicationController::class, 'index'])
                ->name('index');
            Route::get('/stats', [\App\Http\Controllers\CommunicationController::class, 'stats'])
                ->name('stats');
            Route::get('/export', [\App\Http\Controllers\CommunicationController::class, 'export'])
                ->name('export');
            Route::get('/{id}', [\App\Http\Controllers\CommunicationController::class, 'show'])
                ->name('show');
            Route::post('/{id}/resend', [\App\Http\Controllers\CommunicationController::class, 'resend'])
                ->name('resend');
        });

        // Communications par entité
        Route::get('clients/{clientId}/communications', [\App\Http\Controllers\CommunicationController::class, 'forClient'])
            ->name('clients.communications');
        Route::get('users/{userId}/communications', [\App\Http\Controllers\CommunicationController::class, 'forUser'])
            ->name('users.communications');

            // API Routes for autocomplete and search
            Route::prefix('api')->name('api.')->group(function () {
                Route::get('suppliers/search', [\App\Http\Controllers\SupplierController::class, 'searchSuppliers'])
                    ->name('suppliers.search');
                Route::get('devices/search', [\App\Http\Controllers\DeviceController::class, 'searchDevices'])
                    ->name('devices.search');
                Route::get('staff', [\App\Http\Controllers\InstallationAssignmentController::class, 'staff'])
                    ->name('staff');
                Route::get('contracts/{uuid}/sub-products', [\App\Http\Controllers\ContractController::class, 'apiSubProducts'])
                    ->name('contracts.sub-products');

                // Generic API file routes
                Route::apiFileRoutes('suppliers', \App\Http\Controllers\SupplierController::class);
                Route::apiFileRoutes('devices', \App\Http\Controllers\DeviceController::class);
                Route::apiFileRoutes('products', \App\Http\Controllers\ProductController::class);
                Route::apiFileRoutes('orders', \App\Http\Controllers\OrderController::class);
            });
    });

    // Installation schedule routes - protected by client.active
    Route::middleware('client.active')->group(function () {
        Route::prefix('client')->name('client.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ClientController::class, 'home'])
                ->name('home');

            // Installation mode choice (onboarding – first login)
            Route::get('/installation-setup', [\App\Http\Controllers\ClientInstallationChoiceController::class, 'show'])
                ->name('installation-setup');
            Route::post('/installation-setup', [\App\Http\Controllers\ClientInstallationChoiceController::class, 'store'])
                ->name('installation-setup.store');

            // Contract routes
            Route::get('/contracts', [\App\Http\Controllers\ContractController::class, 'index'])
                ->name('contracts.index');
            Route::get('/contracts/{uuid}', [\App\Http\Controllers\ContractController::class, 'show'])
                ->name('contracts.show');

            // Installation routes
            Route::get('/installations', [\App\Http\Controllers\ClientInstallationController::class, 'index'])
                ->name('installations.index');
            Route::get('/installations/{uuid}', [\App\Http\Controllers\ClientInstallationController::class, 'show'])
                ->name('installations.show');

            // ─── Alarm — Centrales d'alarme Hikvision AX PRO ────────
            Route::prefix('installations/{installationUuid}')->name('installations.')->group(function () {
                Route::prefix('alarm')->name('alarm.')->group(function () {
                    // Dashboard temps réel
                    Route::get('/dashboard', [\App\Http\Controllers\ClientAlarmDashboardController::class, 'index'])
                        ->name('dashboard');

                    // Historique & Export
                    Route::get('/history', [\App\Http\Controllers\ClientAlarmHistoryController::class, 'index'])
                        ->name('history');
                    Route::get('/history/export', [\App\Http\Controllers\ClientAlarmHistoryController::class, 'export'])
                        ->name('history.export');

                    // Détail, Arm/Disarm et gestion utilisateurs panel par InstallationDevice
                    Route::prefix('devices/{uuid}')->name('devices.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\ClientAlarmDeviceController::class, 'show'])
                            ->name('show');
                        Route::post('/arm', [\App\Http\Controllers\ClientAlarmDeviceController::class, 'arm'])
                            ->name('arm');
                        Route::post('/disarm', [\App\Http\Controllers\ClientAlarmDeviceController::class, 'disarm'])
                            ->name('disarm');

                        // Gestion utilisateurs panel (administrator uniquement)
                        Route::get('/panel-users', [\App\Http\Controllers\ClientAlarmPanelUserController::class, 'index'])
                            ->name('panel-users.index');
                        Route::post('/panel-users', [\App\Http\Controllers\ClientAlarmPanelUserController::class, 'store'])
                            ->name('panel-users.store');
                        Route::put('/panel-users/{userId}', [\App\Http\Controllers\ClientAlarmPanelUserController::class, 'update'])
                            ->name('panel-users.update');
                        Route::delete('/panel-users/{userId}', [\App\Http\Controllers\ClientAlarmPanelUserController::class, 'destroy'])
                            ->name('panel-users.destroy');
                    });
                });
            });
        });

        Route::get('/installation/{uuid}/schedule', [InstallationController::class, 'toSchedule'])
            ->name('installation.schedule');
        Route::post('/installation/{uuid}/schedule', [InstallationController::class, 'storeSchedule'])
            ->name('installation.schedule.store');
    });
});
