<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SoldeController extends Controller
{
    /**
     * Retourne le solde de l'utilisateur connecté.
     */
    public function getSolde(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        return response()->json([
            'solde' => $user->solde ?? 0.0, // Valeur par défaut si null
        ]);
    }
}
