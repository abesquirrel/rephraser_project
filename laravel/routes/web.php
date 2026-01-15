<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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
