<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{


    public function dashboard()
    {
        $totalClients = Client::count();
        $totalRevendeurs = User::where('role', 'revendeur')->count();
        $totalTransactions = Transaction::count();

        $transactionsValidées = Transaction::where('statut', 'valide')->count();
        $transactionsRejetées = Transaction::where('statut', 'rejeté')->count();
        $transactionsEnAttente = Transaction::whereNull('statut')->count();

        $totalCommission = Transaction::where('statut', 'valide')
            ->sum(\DB::raw('montant * 0.3'));

        $totalPaiementValide = Transaction::where('statut', 'valide')
            ->sum('montant');

        // Top 3 Revendeurs par solde
        $topRevendeursSolde = User::where('role', 'revendeur')
            ->orderBy('solde', 'desc')
            ->take(3)
            ->get(['id', 'name', 'email', 'solde']);

        // Top 3 Revendeurs par nombre de transactions
        $topRevendeursTransactions = User::where('role', 'revendeur')
            ->withCount(['transactions' => function ($query) {
                $query->where('statut', 'valide');
            }])
            ->orderBy('transactions_count', 'desc')
            ->take(3)
            ->get(['id', 'name', 'email']);

        // Transaction mensuelle (graphique)
        $transactionsParMois = Transaction::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois, COUNT(*) as total")
            ->where('statut', 'valide')
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        return response()->json([
            'total_clients' => $totalClients,
            'total_revendeurs' => $totalRevendeurs,
            'total_transactions' => $totalTransactions,
            'transactions_validées' => $transactionsValidées,
            'transactions_rejetées' => $transactionsRejetées,
            'transactions_en_attente' => $transactionsEnAttente,
            'total_commission' => round($totalCommission, 2),
            'total_paiement_valide' => round($totalPaiementValide, 2),
            'top_revendeurs_solde' => $topRevendeursSolde,
            'top_revendeurs_transaction' => $topRevendeursTransactions,
            'transactions_par_mois' => $transactionsParMois,
            
        ]);
    }

}
