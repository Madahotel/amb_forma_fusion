<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SoldeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\PdfExportController;
use App\Http\Controllers\Api\NotificationController; 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 🔓 PUBLIC: Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔐 AUTHENTICATED USERS
Route::middleware(['auth:sanctum'])->group(function () {

    // 📌 Profile & Logout
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 📁 Clients (CRUD)
    Route::apiResource('clients', ClientController::class);

    // 📄 Transactions (accessible à tous pour list & show)
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);

    // 🔔 Notifications (accessible à tous les rôles)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::get('/notifications/unread/count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // 💼 REVENDEUR ONLY
    Route::middleware(['isRevendeur'])->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store']);
        Route::get('/solde', [SoldeController::class, 'getSolde']);
        Route::get('/export-pdf/transaction/{id}', [PdfExportController::class, 'export']);
    });

    // 🛠️ ADMIN ONLY
    Route::middleware(['isAdmin'])->group(function () {
        // ➕ Gestion des revendeurs
        Route::post('/register-revendeur', [UserController::class, 'registerRevendeur']);
        Route::get('/revendeurs', [UserController::class, 'indexRevendeurs']);
        Route::put('/revendeurs/{id}', [UserController::class, 'updateRevendeur']);
        Route::delete('/revendeurs/{id}', [UserController::class, 'destroyRevendeur']);

        // 📊 Dashboard & Export Excel
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/export-transactions', [ExportController::class, 'exportTransactions']);

        // ✅ Validation transaction
        Route::put('/transactions/{id}', [TransactionController::class, 'update']);
        Route::post('/register-client', [ClientController::class, 'registerClient']);

    });
});
