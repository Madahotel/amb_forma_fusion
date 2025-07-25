<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    // GET ALL CLIENTS (ADMIN)
    public function index()
    {
        return Client::with('revendeur')
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'pays' => 'nullable|string|max:100',
            'montant_paye' => 'nullable|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'revendeur_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client = Client::create([
            'name' => $request->nom,
            'email' => $request->email,
            'phone' => $request->telephone,
            'pays' => $request->pays,
            'revendeur_id' => $request->revendeur_id,
            'statut_paiement' => 'Non payé',
            'montant_paye' => $request->montant_paye ?? 0,
            'date_paiement' => $request->date_paiement,
        ]);

        return response()->json([
            'message' => 'Client enregistré avec succès',
            'client' => $client->load('revendeur'),
        ], 201);
    }

    // GET ONE CLIENT
    public function show($id)
    {
        $client = Client::with('revendeur', 'transactions')
            ->findOrFail($id);

        return response()->json($client);
    }

    // UPDATE CLIENT
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:clients,email,' . $client->id,
            'telephone' => 'nullable|string|max:20',
            'pays' => 'nullable|string|max:100',
            'revendeur_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client->update($request->only([
            'name',
            'email',
            'telephone',
            'pays',
            'revendeur_id'
        ]));

        return response()->json([
            'message' => 'Client mis à jour',
            'client' => $client->fresh('revendeur')
        ]);
    }

    // DELETE CLIENT
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(['message' => 'Client supprimé']);
    }

    // CLIENTS PAR REVENDEUR (ADMIN)
    public function getClientsByRevendeur($revendeurId)
    {
        $clients = Client::with(['transactions' => function ($query) {
            $query->latest()->limit(1);
        }])
            ->where('revendeur_id', $revendeurId)
            ->latest()
            ->get();

        return response()->json($clients);
    }

    // MES CLIENTS (REVENDEUR)
    public function myClients(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'revendeur') {
            return response()->json(['error' => 'Accès réservé aux revendeurs ou utilisateur non authentifié'], 403);
        }

        $clients = Client::with(['latestTransaction'])
            ->where('revendeur_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($client) {
                // Pour le débogage, vous pouvez ajouter un dd() ici pour voir la structure exacte
                // dd($client->latestTransaction); 

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'telephone' => $client->telephone,
                    'statut_paiement' => $client->statut_paiement ?? 'Non payé',
                    'montant_paye' => $client->montant_paye ?? 0,
                    // Récupérer la date de validation de la dernière transaction
                    'date_validation' => $client->latestTransaction?->date_validation,
                    'montant' => $client->latestTransaction?->montant ?? 0,
                    'dernier_statut' => $client->latestTransaction?->statut ?? 'Non payé',
                ];
            });

        return response()->json($clients);
    }

    // METTRE À JOUR LE STATUT DE PAIEMENT (ADMIN)
public function updateStatutPaiement(Request $request, $id)
{
    \DB::beginTransaction();
    try {
        $validated = $request->validate([
            'statut_paiement' => 'required|in:non payé,partiel,total payé', // Notez les minuscules
            'montant_paye' => 'nullable|numeric|min:0',
        ]);

        $client = Client::with('revendeur')->findOrFail($id);

        // Mise à jour du client (conserve la casse originale pour l'affichage)
        $client->update([
            'statut_paiement' => ucfirst($validated['statut_paiement']), // Première lettre en majuscule
            'date_paiement' => now(),
            'montant_paye' => $request->montant_paye ?? $client->montant_paye
        ]);

        // Enregistrement de la transaction (en minuscules)
        $transactionData = [
            'client_id' => $client->id,
            'montant' => $request->montant_paye ?? $client->montant_paye ?? 0,
            'statut' => $validated['statut_paiement'], // Déjà en minuscules
            'date_validation' => now(),
            'type' => 'paiement',
            'description' => "Statut changé à: ".$validated['statut_paiement'],
        ];

        if ($client->revendeur_id) {
            $transactionData['revendeur_id'] = $client->revendeur_id;
        }

        Transaction::create($transactionData);

        \DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'client' => $client->fresh()
        ]);

    } catch (\Exception $e) {
        \DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // VALIDER LE PAIEMENT FINAL ET VERSER COMMISSION (ADMIN)
public function validerPaiement($id)
{
    $client = Client::findOrFail($id);

    if ($client->statut_paiement !== 'Total payé') {
        return response()->json(['message' => 'Le statut doit être "Total payé" pour valider'], 400);
    }

    if (!$client->revendeur_id) {
        return response()->json(['message' => 'Aucun revendeur affilié'], 400);
    }

    $revendeur = $client->revendeur;

    if (!$revendeur) {
        return response()->json(['message' => 'Revendeur introuvable'], 404);
    }

    // Calcul de la commission (30%)
    $commission = $client->montant_paye * 0.30;

    // Ajout au solde du revendeur
    $revendeur->solde += $commission;
    $revendeur->save();

    return response()->json([
        'message' => 'Paiement validé et 30% versé au solde du revendeur',
        'client' => $client->load('revendeur'),
        'commission' => $commission,
    ]);
}


    // IMPORTER UN CLIENT DEPUIS UNE SOURCE EXTERNE
    public function importClient(string $email)
    {
        // Dans un cas réel, vous feriez ici un appel à une API externe
        // Pour l'exemple, nous allons simuler la récupération d'un client
        $externalClientData = [
            'email' => $email,
            'nom' => 'Client Externe ' . uniqid(),
            'telephone' => '0123456789',
            'pays' => 'Madagascar',
        ];

        $existingClient = Client::where('email', $email)->first();

        if ($existingClient) {
            return response()->json(['message' => 'Le client existe déjà dans la base de données.', 'client' => $existingClient], 200);
        }

        $client = Client::create([
            'name' => $externalClientData['nom'],
            'email' => $externalClientData['email'],
            'telephone' => $externalClientData['telephone'],
            'pays' => $externalClientData['pays'],
            'statut_paiement' => 'Non payé',
            'montant_paye' => 0,
        ]);

        return response()->json(['message' => 'Client importé avec succès', 'client' => $client], 201);
    }

    // METHODE PRIVEE POUR TRAITER LES COMMISSIONS
private function processCommission(Client $client)
{
    if ($client->commission_verse) {
        return; // Commission déjà versée, on ne fait rien
    }

    if (!$client->montant_paye || $client->montant_paye <= 0) {
        return; // Pas de montant payé → pas de commission
    }

    $revendeur = User::findOrFail($client->revendeur_id);
    $commission = $client->montant_paye * 0.3; // 30%

    $revendeur->increment('solde', $commission);

    // Enregistrement de la commission comme transaction
    Transaction::create([
        'revendeur_id' => $revendeur->id,
        'client_id' => $client->id,
        'montant' => $commission,
        'type' => 'commission',
        'statut' => 'valide',
        'date_validation' => now(),
        'description' => "Commission pour client {$client->name}",
    ]);

    // Marquer la commission comme versée
    $client->update([
        'commission_verse' => true,
    ]);

    // Marquer les paiements liés comme "commission versée"
    Transaction::where('client_id', $client->id)
        ->where('type', 'paiement')
        ->where('commission_paid', false)
        ->update(['commission_paid' => true]);
}

}
