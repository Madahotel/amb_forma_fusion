<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\RetraitDemandeNotification;

class TransactionController extends Controller
{
public function index(Request $request)
{
    $user = $request->user();

    $query = Transaction::with(['client', 'revendeur']); // ⬅️ Ajout important

    if ($user->role === 'revendeur') {
        $query->where('revendeur_id', $user->id);
    }

    if ($request->filled('start') && $request->filled('end')) {
        $query->whereBetween('created_at', [$request->start, $request->end]);
    }

    return response()->json(
        $query->orderBy('created_at', 'desc')->get()
    );
}




    // REVENUDEUR: Créer une demande
    public function store(Request $request)
    {
        $request->validate([
            'montant' => 'required|numeric',
            'moyen_paiement' => 'required|in:mvola,orange,banque,autre',
        ]);

        $transaction = Transaction::create([
            'revendeur_id' => Auth::id(),
            'montant' => $request->montant,
            'moyen_paiement' => $request->moyen_paiement,
            'statut' => 'en_attente',
            'date_demande' => Carbon::now(),
        ]);
        
        

        return response()->json(['message' => 'Demande envoyée', 'transaction' => $transaction]);
               // Notify admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new RetraitDemandeNotification($transaction));
        }
            
    }

public function update(Request $request, $id)
{
    $request->validate([
        'statut' => 'required|in:valide,refuse',
        'note' => 'nullable|string'
    ]);

    $transaction = Transaction::findOrFail($id);
    $transaction->statut = $request->statut;
    $transaction->note = $request->note;
    $transaction->admin_id_validator = Auth::id();
    $transaction->date_validation = now();
    $transaction->save();

    // Si validé, déduire le solde du revendeur
    if ($request->statut === 'valide') {
        $revendeur = User::find($transaction->revendeur_id);
        $revendeur->solde -= $transaction->montant;
        $revendeur->save();
    }

    return response()->json(['message' => 'Transaction mise à jour']);
}


    // GET ONE
    public function show($id)
    {
        $transaction = Transaction::with(['revendeur', 'validator'])->findOrFail($id);
        return response()->json($transaction);
    }
}

