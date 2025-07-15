<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    // GET ALL CLIENTS (ADMIN)
    public function index()
    {
        return Client::with('revendeur')->latest()->get();
    }

    // STORE CLIENT
public function store(Request $request)
{
    $request->validate([
        'name' => 'required',
        'email' => 'nullable|email',
        'phone' => 'nullable|string',
        'montant_paye' => 'required|numeric',
        'revendeur_id' => 'required|exists:users,id',
        'date_paiement' => 'required|date'
    ]);

    // Création client
    $client = Client::create($request->all());

    // Calculer commission 30%
    $revendeur = User::find($request->revendeur_id);
    $commission = $request->montant_paye * 0.3;

    // Ajouter commission au solde revendeur
    $revendeur->solde += $commission;
    $revendeur->save();

    return response()->json(['message' => 'Client créé avec commission ajoutée', 'client' => $client]);
}


    // GET ONE CLIENT
    public function show($id)
    {
        $client = Client::with('revendeur')->findOrFail($id);
        return response()->json($client);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $client->update($request->all());

        return response()->json(['message' => 'Client mis à jour', 'client' => $client]);
    }

    // DELETE
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(['message' => 'Client supprimé']);
    }
}

