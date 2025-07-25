<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class UserController extends Controller
{
    public function registerRevendeur(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'pays' => 'nullable|string|max:100',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $clientData = null;

        try {
            $response = Http::timeout(5)->get('https://dossier.forma-fusion.com/api/clients/email/' . $request->email);
            if ($response->successful()) {
                $clientData = $response->json();
            }
        } catch (\Exception $e) {
            Log::error("Erreur appel API client: " . $e->getMessage());
        }

        try {
            DB::beginTransaction();

            $id = DB::table('users')->insertGetId([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'revendeur',
                'solde' => 0,
                'pays' => $request->pays ?? ($clientData['pays'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $codeAffiliation = 'AFF-' . strtoupper(Str::random(6));

            // Redirection vers https://dossier.forma-fusion.com (corrigé ici)
            $frontendUrl = 'https://dossier.forma-fusion.com';
            $affiliationLink = $frontendUrl . '/register?ref=' . $codeAffiliation;

            DB::table('users')->where('id', $id)->update([
                'code_affiliation' => $codeAffiliation,
                'affiliation_link' => $affiliationLink,
                'updated_at' => now(),
            ]);

            DB::commit();

            $user = DB::table('users')->where('id', $id)->first();

            return response()->json([
                'message' => 'Revendeur créé avec succès',
                'user' => $user,
                'code_affiliation' => $user->code_affiliation,
                'link' => $user->affiliation_link,
                'client_data' => $clientData,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur création revendeur: " . $e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la création du revendeur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexRevendeurs()
    {
        $revendeurs = DB::table('users')->where('role', 'revendeur')->get();

        return response()->json([
            'message' => 'Liste des revendeurs',
            'revendeurs' => $revendeurs,
        ]);
    }


    public function updateRevendeur(Request $request, $id)
    {
        $user = DB::table('users')
            ->where('role', 'revendeur')
            ->where('id', $id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:6|confirmed'
        ]);

        $data = ['updated_at' => now()];

        if ($request->filled('name')) $data['name'] = $request->name;
        if ($request->filled('email')) $data['email'] = $request->email;
        if ($request->filled('password')) $data['password'] = bcrypt($request->password);

        DB::table('users')->where('id', $id)->update($data);

        $updatedUser = DB::table('users')->where('id', $id)->first();

        return response()->json([
            'message' => 'Revendeur mis à jour avec succès',
            'user' => $updatedUser
        ]);
    }

    public function destroyRevendeur($id)
    {
        $user = DB::table('users')
            ->where('role', 'revendeur')
            ->where('id', $id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        DB::table('users')->where('id', $id)->delete();

        return response()->json(['message' => 'Revendeur supprimé avec succès']);
    }
}
