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

public function registerClient(Request $request)
{
    $request->validate([
        'nom' => 'required|string|max:255',
        'email' => 'required|email|unique:clients',
        'pays' => 'nullable|string|max:100',
        'code_affiliation' => 'nullable|string|exists:users,code_affiliation',
    ]);

    $client = new Client();
    $client->nom = $request->nom;
    $client->email = $request->email;
    $client->pays = $request->pays;

    if ($request->filled('code_affiliation')) {
        $revendeur = User::where('code_affiliation', $request->code_affiliation)->first();
        $client->revendeur_id = $revendeur->id;
    }

    $client->save();

    return response()->json([
        'message' => 'Client enregistré avec succès',
        'client' => $client,
        'revendeur_affilie' => $client->revendeur?->name,
    ]);
}



    // STORE CLIENT
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:clients',
        'password' => 'required|string|min:6',
        'ref' => 'nullable|string' // code affiliation
    ]);

    $client = new Client();
    $client->name = $validated['name'];
    $client->email = $validated['email'];
    $client->password = bcrypt($validated['password']);

    // Raha nisy code ref
    if (!empty($request->ref)) {
        $revendeur = User::where('code_affiliation', $request->ref)->where('role', 'revendeur')->first();
        if ($revendeur) {
            $client->revendeur_id = $revendeur->id;
        }
    }

    $client->save();

    return response()->json(['message' => 'Client créé avec succès', 'client' => $client]);
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

