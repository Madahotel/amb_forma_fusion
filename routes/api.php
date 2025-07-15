<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SoldeController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TransactionController;

// 🔐 Auth Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔐 Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {

    // 🔸 Auth infos
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 🔸 Clients (accessible to both admin and revendeur if needed)
    Route::apiResource('clients', ClientController::class);

    // 🔸 Transaction routes accessibles à tous (liste & show)
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);

    // 🔹 Revendeur only
    Route::middleware('isRevendeur')->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store']);
        Route::get('/solde', [SoldeController::class, 'getSolde']);
    });

    // 🔹 Admin only
    Route::middleware('isAdmin')->group(function () {
        Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    });
});
