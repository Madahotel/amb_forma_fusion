<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Notifications\RetraitDemandeNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DB::table('transactions')
            ->select('transactions.*', 'u.name as revendeur_name', 'c.name as client_name')
            ->leftJoin('users as u', 'transactions.revendeur_id', '=', 'u.id')
            ->leftJoin('users as c', 'transactions.client_id', '=', 'c.id');

        if ($user->role === 'revendeur') {
            $query->where('transactions.revendeur_id', $user->id);
        }

        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('transactions.created_at', [$request->start, $request->end]);
        }

        return response()->json(
            $query->orderBy('transactions.created_at', 'desc')->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'montant' => 'required|numeric',
            'moyen_paiement' => 'required|in:mvola,orange,banque,autre',
        ]);

        $user = Auth::user();

        // Vérifier solde
        if ($user->solde <= 0) {
            return response()->json([
                'message' => 'Solde insuffisant pour effectuer une transaction.'
            ], 403);
        }

        if ($request->montant > $user->solde) {
            return response()->json([
                'message' => 'Le montant demandé dépasse votre solde disponible.'
            ], 403);
        }

        // Insert transaction
        $id = DB::table('transactions')->insertGetId([
            'revendeur_id' => $user->id,
            'montant' => $request->montant,
            'moyen_paiement' => $request->moyen_paiement,
            'statut' => 'en_attente',
            'date_demande' => Carbon::now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transaction = Transaction::findOrFail($id);

        // Notify admins
        $admins = DB::table('users')->where('role', 'admin')->get();
        foreach ($admins as $admin) {
            // Tokony atao hoe model User ilay Notification
            \App\Models\User::find($admin->id)->notify(
                new RetraitDemandeNotification($transaction)
            );
        }

        return response()->json([
            'message' => 'Demande envoyée',
            'transaction' => $transaction
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:valide,refuse',
            'note' => 'nullable|string'
        ]);

        $transaction = DB::table('transactions')->where('id', $id)->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable'], 404);
        }

        DB::table('transactions')->where('id', $id)->update([
            'statut' => $request->statut,
            'note' => $request->note,
            'admin_id_validator' => Auth::id(),
            'date_validation' => now(),
            'updated_at' => now(),
        ]);

        // Si validé, déduire solde
        if ($request->statut === 'valide') {
            $revendeur = DB::table('users')->where('id', $transaction->revendeur_id)->first();

            if ($revendeur) {
                $nouveauSolde = $revendeur->solde - $transaction->montant;
                DB::table('users')->where('id', $revendeur->id)->update([
                    'solde' => $nouveauSolde,
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json(['message' => 'Transaction mise à jour']);
    }

    public function show($id)
    {
        $transaction = DB::table('transactions')
            ->leftJoin('users as r', 'transactions.revendeur_id', '=', 'r.id')
            ->leftJoin('users as v', 'transactions.admin_id_validator', '=', 'v.id')
            ->select('transactions.*', 'r.name as revendeur_name', 'v.name as validator_name')
            ->where('transactions.id', $id)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction non trouvée'], 404);
        }

        return response()->json($transaction);
    }
}
