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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->post('/client/new', [ClientController::class, 'create']);
Route::middleware('auth:sanctum')->put('/client/{uuid}/step/{step}', [ClientController::class, 'updateStep']);
Route::middleware('auth:sanctum')->post('/client/{uuid}/store-criterias', [ClientController::class, 'storeCriterias']);
