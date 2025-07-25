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
    // apiResource gère : GET /clients (index), POST /clients (store), GET /clients/{id} (show),
    // PUT/PATCH /clients/{id} (update), DELETE /clients/{id} (destroy)
    Route::apiResource('clients', ClientController::class);

    // Route pour l'importation de client par email (renommée la méthode dans le contrôleur)
    Route::get('/import-client/{email}', [ClientController::class, 'importClient']); // Corrigé: importClient

    // Route pour les clients d'un revendeur
    Route::get('/mes-clients', [ClientController::class, 'myClients']);

    // Route pour la mise à jour du statut de paiement
    Route::patch('/clients/{id}/update-statut', [ClientController::class, 'updateStatutPaiement']); // Corrigé: update-statut

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
        
        // Route pour valider le paiement final (déjà correcte)
        Route::post('/clients/{id}/valider-paiement', [ClientController::class, 'validerPaiement']);

        // ✅ Validation transaction
        Route::put('/transactions/{id}', [TransactionController::class, 'update']);
        
        // Ces routes sont maintenant gérées par apiResource ou renommées/supprimées
        // Route::post('/register-client', [ClientController::class, 'registerClient']); // Supprimée (gérée par apiResource)
        // Route::get('/external-client/{email}', [ClientController::class, 'getClientFromExternal']); // Supprimée (remplacée par import-client)
    });
});
