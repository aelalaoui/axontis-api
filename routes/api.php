<?php

use App\Http\Controllers\ClientController;
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
Route::post('/client/{uuid}/store-criterias', [ClientController::class, 'storeCriterias']);
Route::get('/client/{uuid}/property/{property}/value/{value}/offer', [ClientController::class, 'calculateOffer']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::put('/client/{uuid}/step/{step}', [ClientController::class, 'updateStep']);
});

// Entity search routes - accessible for authenticated web users
Route::middleware('web')->group(function () {
    Route::prefix('entities')->group(function () {
        Route::get('/types', [\App\Http\Controllers\Api\EntitySearchController::class, 'getEntityTypes']);
        Route::get('/search', [\App\Http\Controllers\Api\EntitySearchController::class, 'search']);
    });
});
