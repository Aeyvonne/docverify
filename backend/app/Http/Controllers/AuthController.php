<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel émetteur.
     * Les admins sont créés uniquement en base, jamais via cette route.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'nom'              => ['required', 'string', 'max:100'],
            'prenom'           => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'telephone'        => ['nullable', 'string', 'max:20'],
            'nom_institution'  => ['nullable', 'string', 'max:255'],
            'type_institution' => ['nullable', 'string', 'max:100'],
            'adresse'          => ['nullable', 'string', 'max:255'],
        ]);

        // Un particulier est auto-certifié : pas besoin de validation admin
        $isParticulier = ($data['type_institution'] ?? null) === 'particulier';

        $user = User::create([
            'nom'              => $data['nom'],
            'prenom'           => $data['prenom'],
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'role'             => 'emetteur',
            'telephone'        => $data['telephone'] ?? null,
            'nom_institution'  => $data['nom_institution'] ?? null,
            'type_institution' => $data['type_institution'] ?? null,
            'adresse'          => $data['adresse'] ?? null,
            'is_active'        => true,
            // Particulier → certifié automatiquement (pas de validation admin nécessaire)
            // Institution → en attente de certification par l'admin
            'is_certified'     => $isParticulier,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Connexion — retourne un token Sanctum.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Compte désactivé. Contactez l\'administrateur.'], 403);
        }

        // Mise à jour de la dernière connexion
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion — révoque le token courant.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    /**
     * Retourne l'utilisateur authentifié.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
