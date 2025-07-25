<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6',
            'role' => 'in:admin,revendeur',
        ]);

        // Insertion via Query Builder
        DB::table('users')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'revendeur',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Utilisateur enregistré avec succès']);
    }

    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Récupérer l'utilisateur avec Query Builder
        $userData = DB::table('users')->where('email', $request->email)->first();

        if (! $userData || ! Hash::check($request->password, $userData->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe invalide'],
            ]);
        }

        // On recharge l'utilisateur en tant que modèle Eloquent juste pour le token
        $user = User::find($userData->id);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    // ME
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
