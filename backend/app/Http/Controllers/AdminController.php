<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Liste tous les émetteurs.
     */
    public function indexEmetteurs()
    {
        $emetteurs = User::where('role', 'emetteur')
            ->latest()
            ->get();

        return response()->json($emetteurs);
    }

    /**
     * Crée un nouveau compte émetteur (par l'admin).
     */
    public function createEmetteur(Request $request)
    {
        $data = $request->validate([
            'nom'              => ['required', 'string', 'max:100'],
            'prenom'           => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8'],
            'telephone'        => ['nullable', 'string', 'max:20'],
            'nom_institution'  => ['nullable', 'string', 'max:255'],
            'type_institution' => ['nullable', 'string', 'max:100'],
            'adresse'          => ['nullable', 'string', 'max:255'],
            'is_certified'     => ['sometimes', 'boolean'],
        ]);

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
            'is_certified'     => $data['is_certified'] ?? false,
        ]);

        return response()->json($user, 201);
    }

    /**
     * Modifie un compte émetteur existant.
     */
    public function updateEmetteur(Request $request, User $user)
    {
        // L'admin ne peut modifier que les émetteurs
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $data = $request->validate([
            'nom'              => ['sometimes', 'string', 'max:100'],
            'prenom'           => ['sometimes', 'string', 'max:100'],
            'email'            => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'telephone'        => ['nullable', 'string', 'max:20'],
            'nom_institution'  => ['nullable', 'string', 'max:255'],
            'type_institution' => ['nullable', 'string', 'max:100'],
            'adresse'          => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($data);

        return response()->json($user);
    }

    /**
     * Active ou désactive un compte émetteur.
     */
    public function toggleActive(User $user)
    {
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        $statut = $user->is_active ? 'activé' : 'désactivé';

        return response()->json([
            'message'   => "Compte {$statut} avec succès.",
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Certifie un émetteur (is_certified = true).
     * Marque également la demande de certification comme approuvée si elle existe.
     */
    public function certifyEmetteur(Request $request, User $user)
    {
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $user->update(['is_certified' => true]);

        // Approuver la demande en attente si elle existe
        $demande = $user->demandesCertifications()
            ->where('statut', 'en_attente')
            ->latest()
            ->first();

        if ($demande) {
            $demande->update([
                'statut'    => 'approuvee',
                'traite_par' => $request->user()->id,
                'traite_le' => now(),
            ]);
        }

        return response()->json([
            'message'      => 'Émetteur certifié avec succès.',
            'is_certified' => true,
        ]);
    }

    /**
     * Révoque la certification d'un émetteur.
     */
    public function revokeEmetteurCertification(User $user)
    {
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $user->update(['is_certified' => false]);

        return response()->json([
            'message'      => 'Certification révoquée.',
            'is_certified' => false,
        ]);
    }

    /**
     * Affiche un émetteur spécifique.
     */
    public function showEmetteur(User $user)
    {
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        return response()->json($user->load('demandesCertifications'));
    }
}
