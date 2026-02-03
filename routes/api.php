<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InstallationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/client/new', [ClientController::class, 'create']);
Route::post('/client/{uuid}', [ClientController::class, 'updateDetails']);
Route::post('/client/{uuid}/store-criterias', [ClientController::class, 'storeCriterias']);
Route::get('/client/{uuid}/property/{property}/value/{value}/offer', [ClientController::class, 'calculateOffer']);
Route::put('/client/{uuid}/step/{step}', [ClientController::class, 'updateStep']);

Route::get('/cities/autocomplete', [CityController::class, 'autocomplete']);

Route::post('/installation/new', [InstallationController::class, 'create']);
Route::post('/contract/generate/client/{uuid}', [\App\Http\Controllers\ContractController::class, 'generate']);

// Payment routes - PCI-DSS compliant (no card data processed by backend)
Route::post('/payments/deposit/init', [\App\Http\Controllers\PaymentController::class, 'initializePayment']);
Route::get('/payments/{paymentUuid}', [\App\Http\Controllers\PaymentController::class, 'getPaymentDetails']);
Route::post('/payments/{paymentUuid}/refund', [\App\Http\Controllers\PaymentController::class, 'refundPayment']);

// Stripe webhook endpoint (publicly accessible, signature verified by provider)
Route::post('/webhooks/stripe', [\App\Http\Controllers\PaymentController::class, 'handleStripeWebhook']);

// Signature webhook endpoint (publicly accessible)
Route::post('/signature/webhook/{provider}', [\App\Http\Controllers\SignatureController::class, 'handleWebhook']);

// =============================================================================
// HIKVISION ALARM INTEGRATION
// =============================================================================

// Webhook endpoints (publicly accessible, verified by middleware)
Route::prefix('webhooks/hikvision')->middleware('hikvision.webhook')->group(function () {
    Route::post('/alarm', [\App\Http\Controllers\Api\AlarmWebhookController::class, 'handleAlarm']);
    Route::post('/heartbeat', [\App\Http\Controllers\Api\AlarmWebhookController::class, 'handleHeartbeat']);
    Route::get('/health', [\App\Http\Controllers\Api\AlarmWebhookController::class, 'health']);
});

// Dashboard routes - Requires authentication and manager/administrator role
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Entity search routes - accessible for authenticated web users
Route::middleware('web')->group(function () {
    Route::middleware('role:manager,administrator')->group(function () {
        Route::get('/dashboard/charts', [\App\Http\Controllers\Api\DashboardController::class, 'getChartData']);
        Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'getStats']);

        // =============================================================================
        // ALARM DEVICES MANAGEMENT (requires manager/admin role)
        // =============================================================================
        Route::prefix('alarm-devices')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'stats']);
            Route::post('/', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'store']);
            Route::post('/refresh-status', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'refreshStatus']);
            Route::get('/{uuid}', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'show']);
            Route::put('/{uuid}', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'update']);
            Route::delete('/{uuid}', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'destroy']);
            Route::post('/{uuid}/test-connection', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'testConnection']);
            Route::get('/{uuid}/info', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'getDeviceInfo']);
            Route::get('/{uuid}/status', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'getStatus']);
            Route::post('/{uuid}/arm', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'arm']);
            Route::post('/{uuid}/disarm', [\App\Http\Controllers\Api\AlarmDeviceController::class, 'disarm']);
            Route::get('/{uuid}/events', [\App\Http\Controllers\Api\AlarmEventController::class, 'forDevice']);
        });

        // =============================================================================
        // ALARM EVENTS (requires manager/admin role)
        // =============================================================================
        Route::prefix('alarm-events')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AlarmEventController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Api\AlarmEventController::class, 'stats']);
            Route::get('/critical', [\App\Http\Controllers\Api\AlarmEventController::class, 'critical']);
            Route::get('/unprocessed', [\App\Http\Controllers\Api\AlarmEventController::class, 'unprocessed']);
            Route::get('/{uuid}', [\App\Http\Controllers\Api\AlarmEventController::class, 'show']);
        });
    });
    Route::prefix('entities')->group(function () {
        Route::get('/types', [\App\Http\Controllers\Api\EntitySearchController::class, 'getEntityTypes']);
        Route::get('/search', [\App\Http\Controllers\Api\EntitySearchController::class, 'search']);
    });
});
