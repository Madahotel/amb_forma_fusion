<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalClients = DB::table('clients')->count();
        $totalRevendeurs = DB::table('users')->where('role', 'revendeur')->count();
        $totalTransactions = DB::table('transactions')->count();

        $transactionsValidees = DB::table('transactions')->where('statut', 'valide')->count();
        $transactionsRejetees = DB::table('transactions')->where('statut', 'rejeté')->count();
        $transactionsEnAttente = DB::table('transactions')->whereNull('statut')->count();

        $totalCommission = DB::table('transactions')
            ->where('statut', 'valide')
            ->sum(DB::raw('montant * 0.3'));

        $totalPaiementValide = DB::table('transactions')
            ->where('statut', 'valide')
            ->sum('montant');

        // Top 3 Revendeurs par solde
        $topRevendeursSolde = DB::table('users')
            ->where('role', 'revendeur')
            ->orderByDesc('solde')
            ->limit(3)
            ->select('id', 'name', 'email', 'solde')
            ->get();

        // Top 3 Revendeurs par nombre de transactions validées
        $topRevendeursTransactions = DB::table('transactions')
            ->where('statut', 'valide')
            ->join('users', 'transactions.revendeur_id', '=', 'users.id')
            ->where('users.role', 'revendeur')
            ->select('users.id', 'users.name', 'users.email', DB::raw('COUNT(transactions.id) as total_transactions'))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_transactions')
            ->limit(3)
            ->get();

        // Transactions par mois
        $transactionsParMois = DB::table('transactions')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois, COUNT(*) as total")
            ->where('statut', 'valide')
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        return response()->json([
            'total_clients' => $totalClients,
            'total_revendeurs' => $totalRevendeurs,
            'total_transactions' => $totalTransactions,
            'transactions_validées' => $transactionsValidees,
            'transactions_rejetées' => $transactionsRejetees,
            'transactions_en_attente' => $transactionsEnAttente,
            'total_commission' => round($totalCommission, 2),
            'total_paiement_valide' => round($totalPaiementValide, 2),
            'top_revendeurs_solde' => $topRevendeursSolde,
            'top_revendeurs_transaction' => $topRevendeursTransactions,
            'transactions_par_mois' => $transactionsParMois,
        ]);
    }

    public function validerPaiement($clientId)
    {
        // Récupération client
        $client = DB::table('clients')->where('id', $clientId)->first();

        if (! $client) {
            return response()->json(['message' => 'Client introuvable.'], 404);
        }

        // Mise à jour statut
        DB::table('clients')->where('id', $clientId)->update([
            'statut_paiement' => 'Payé',
        ]);

        $montantTotal = $client->montant_total ?? 0;
        $commission = $montantTotal * 0.30;

        if ($client->revendeur_id) {
            DB::table('users')->where('id', $client->revendeur_id)->increment('compte', $commission);
        }

        return response()->json(['message' => 'Paiement validé et commission versée.']);
    }
}
