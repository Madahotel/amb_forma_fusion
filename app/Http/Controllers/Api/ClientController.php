<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

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
        $client->name = $request->nom;
        $client->email = $request->email;
        $client->pays = $request->pays ?? null;

        // Raha misy affiliation
        if ($request->filled('code_affiliation')) {
            $revendeur = User::where('code_affiliation', $request->code_affiliation)->first();

            if ($revendeur) {
                $client->revendeur_id = $revendeur->id;
            }
            // Raha tsy misy dia atao libre (revendeur_id = null)
        }

        // Fanampiana hafa (tsy voatery)
        $client->montant_paye = 0;
        $client->date_paiement = now(); // raha ilaina

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

    public function getClientsByRevendeur($revendeurId)
    {
        $clients = Client::where('revendeur_id', $revendeurId)->get();
        return response()->json($clients);
    }



    public function importClientFromExternal($email)
    {
        $token = config('services.forma_fusion.token');

        $response = Http::withToken($token)
            ->get("https://marketplace.forma-fusion.com/api/clients/email/$email");

        if ($response->successful()) {
            $data = $response->json();

            // Tsy mamerina client mitovy
            $existingClient = Client::where('email', $data['email'])->first();
            if ($existingClient) {
                return response()->json(['message' => 'Client existe déjà', 'client' => $existingClient]);
            }

            // Mampiditra azy ao amin'ny base-nao
            $client = new Client();
            $client->name = $data['nom'];
            $client->email = $data['email'];
            $client->pays = $data['pays'] ?? null;
            $client->save();

            return response()->json(['message' => 'Client importé avec succès', 'client' => $client]);
        }

        return response()->json(['error' => 'Client non trouvé'], 404);
    }

    public function myClients(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'revendeur') {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        $clients = Client::with(['transactions' => function ($query) {
            $query->latest()->select('id', 'client_id', 'statut', 'montant', 'date_validation');
        }])
            ->where('revendeur_id', $user->id)
            ->latest()
            ->get(['id', 'name', 'email']);

        return response()->json($clients);
    }


    public function updateStatutPaiement(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $request->validate([
            'statut_paiement' => 'required|in:Non payé,Partiel,Total payé',
        ]);

        $client->statut_paiement = $request->statut_paiement;
        $client->save();

        return response()->json(['message' => 'Statut mis à jour', 'client' => $client]);
    }
    // ClientsController.php

    public function validerPaiement($id)
    {
        $client = Client::findOrFail($id);

        if ($client->statut_paiement !== 'Total payé') {
            return response()->json(['message' => 'Le paiement n\'est pas encore total.'], 400);
        }

        if (!$client->revendeur_id) {
            return response()->json(['message' => 'Ce client n\'a pas de revendeur.'], 400);
        }

        $montant = floatval($client->montant_paye);
        $commission = $montant * 0.3;

        $revendeur = User::findOrFail($client->revendeur_id);
        $revendeur->solde += $commission;
        $revendeur->save();

        // (optionnel) Enregistrer dans table transactions
        Transaction::create([
            'revendeur_id' => $revendeur->id,
            'montant' => $commission,
            'type' => 'commission',
            'description' => "Commission client {$client->nom}",
            'statut' => 'valide',
        ]);

        return response()->json(['message' => 'Paiement validé et commission versée.']);
    }
}
