<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RephraseController;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
// Analytics Routes
Route::post('/api/session/start', [RephraseController::class, 'startSession']);
Route::get('/api/kb-stats', [RephraseController::class, 'getKbStats']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\UserAdminController::class, 'index']);
        Route::put('/users/{user}', [\App\Http\Controllers\UserAdminController::class, 'update']);
        Route::delete('/users/{user}', [\App\Http\Controllers\UserAdminController::class, 'destroy']);
    });
});
