<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
public function registerRevendeur(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:100',
        'email' => 'required|email|unique:users,email',
        'pays' => 'nullable|string|max:100', // Atambatra ao amin'ny info avy amin'ny API raha tsy misy
        'password' => 'required|min:6|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    // --- Récupérer info client si inscription faite sur base externe ---
    $clientData = null;
    $response = Http::get('https://marketplace.forma-fusion.com/api/clients/email/' . $request->email);
    if ($response->successful()) {
        $clientData = $response->json();
    }

    // --- Création du revendeur avec données complétées ---
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'revendeur',
        'solde' => 0,
        'pays' => $request->pays ?? ($clientData['pays'] ?? null),
    ]);

    return response()->json([
        'message' => 'Revendeur créé avec succès',
        'user' => $user,
        'code_affiliation' => $user->code_affiliation,
        'link' => $user->affiliation_link,
        'client_data' => $clientData, // Optionnel, fanampiny raha ilaina
    ], 201);
}


    public function indexRevendeurs()
    {
        $revendeurs = User::where('role', 'revendeur')->get();
        return response()->json($revendeurs);
    }

    public function updateRevendeur(Request $request, $id)
    {
        $user = User::where('role', 'revendeur')->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:6|confirmed'
        ]);

        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return response()->json(['message' => 'Revendeur mis à jour', 'user' => $user]);
    }

    public function destroyRevendeur($id)
    {
        $user = User::where('role', 'revendeur')->findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Revendeur supprimé']);
    }
}
