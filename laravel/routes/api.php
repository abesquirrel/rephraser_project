<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RephraseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/rephrase', [RephraseController::class, 'rephrase']);
Route::post('/approve', [RephraseController::class, 'approve']);
Route::post('/upload_kb', [RephraseController::class, 'upload_kb']);
Route::post('/suggest-keywords', [RephraseController::class, 'suggestKeywords']);
Route::get('/audit-logs', [RephraseController::class, 'getAuditLogs']);
Route::get('/models', [RephraseController::class, 'getModels']);
Route::get('/kb-stats', [RephraseController::class, 'getKbStats']);
