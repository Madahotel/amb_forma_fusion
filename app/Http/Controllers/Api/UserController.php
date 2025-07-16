<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function registerRevendeur(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'pays' => 'required|string|max:100',
            'password' => 'required|min:6|confirmed', // garde confirmed si tu envoies confirmation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'revendeur',
            'solde' => 0,
            'pays' => $request->pays,  // Ajoute cette ligne
        ]);

        // Exemple : récupérer le code_affiliation du user (généré dans User model)
        $code_affiliation = $user->code_affiliation ?? null;

        return response()->json([
            'message' => 'Revendeur créé avec succès',
            'user' => $user,
            'code_affiliation' => $code_affiliation,
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
