<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Notification;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Met à jour le profil de l'admin connecté.
     * PUT /api/admin/profil
     */
    public function updateProfil(Request $request)
    {
        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:100'],
            'prenom'    => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'unique:users,email,' . $request->user()->id],
            'telephone' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->update($data);

        return response()->json($request->user()->fresh());
    }

    /**
     * Change le mot de passe de l'admin connecté.
     * PUT /api/admin/password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current'               => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[^a-zA-Z0-9]/'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex'     => 'Le mot de passe doit contenir une majuscule et un caractère spécial.',
            'password.confirmed' => 'La confirmation ne correspond pas.',
        ]);

        if (!Hash::check($request->current, $request->user()->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    /**
     * Retourne les notifications de l'admin connecté.
     * GET /api/admin/notifications
     */
    public function notifications(Request $request)
    {
        $notifs = Notification::where('admin_id', $request->user()->id)
            ->with('demande.user:id,nom,prenom,nom_institution')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'notifications' => $notifs,
            'non_lues'      => $notifs->where('lu', false)->count(),
        ]);
    }

    /**
     * Marque toutes les notifications de l'admin comme lues.
     * PATCH /api/admin/notifications/mark-read
     */
    public function markNotificationsRead(Request $request)
    {
        Notification::where('admin_id', $request->user()->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json(['message' => 'Notifications marquées comme lues.']);
    }

    /**
     * Liste tous les admins.
     * GET /api/admin/admins
     */
    public function indexAdmins()
    {
        $admins = User::where('role', 'admin')
            ->latest()
            ->get(['id', 'nom', 'prenom', 'email', 'telephone', 'is_active', 'created_at', 'last_login_at']);

        return response()->json($admins);
    }

    /**
     * Crée un nouveau compte admin.
     * POST /api/admin/admins
     */
    public function createAdmin(Request $request)
    {
        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:100'],
            'prenom'    => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[^a-zA-Z0-9]/'],
            'telephone' => ['nullable', 'string', 'max:20'],
        ]);

        $admin = User::create([
            'nom'       => $data['nom'],
            'prenom'    => $data['prenom'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'admin',
            'telephone' => $data['telephone'] ?? null,
            'is_active' => true,
        ]);

        return response()->json($admin, 201);
    }

    /**
     * Liste tous les émetteurs.
     * GET /api/admin/emetteurs
     */
    public function indexEmetteurs()
    {
        $emetteurs = User::where('role', 'emetteur')
            ->latest()
            ->get(['id', 'nom', 'prenom', 'email', 'telephone', 'nom_institution', 'type_institution', 'is_active', 'is_certified', 'created_at', 'last_login_at']);

        return response()->json($emetteurs);
    }

    /**
     * Crée un nouveau compte émetteur (par l'admin).
     * POST /api/admin/emetteurs
     */
    public function createEmetteur(Request $request)
    {
        $data = $request->validate([
            'nom'              => ['required', 'string', 'max:100'],
            'prenom'           => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[^a-zA-Z0-9]/'],
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
     * PUT /api/admin/emetteurs/{user}
     */
    public function updateEmetteur(Request $request, User $user)
    {
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

        return response()->json($user->fresh());
    }

    /**
     * Active ou désactive un compte émetteur.
     * PATCH /api/admin/emetteurs/{user}/toggle
     */
    public function toggleActive(User $user)
    {
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message'   => 'Compte ' . ($user->is_active ? 'activé' : 'désactivé') . ' avec succès.',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Certifie un émetteur et approuve sa demande en attente.
     * PATCH /api/admin/emetteurs/{user}/certify
     */
    public function certifyEmetteur(Request $request, User $user)
    {
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $user->update(['is_certified' => true]);

        $demande = $user->demandesCertifications()
            ->where('statut', 'en_attente')
            ->latest()
            ->first();

        if ($demande) {
            $demande->update([
                'statut'     => 'approuvee',
                'traite_par' => $request->user()->id,
                'traite_le'  => now(),
            ]);

            try {
                \Illuminate\Support\Facades\Mail::to($user->email)
                    ->send(new \App\Mail\DemandeApprouvee($demande->load('user')));
            } catch (\Throwable $e) {
                Log::error('Mail DemandeApprouvee failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json(['message' => 'Émetteur certifié avec succès.', 'is_certified' => true]);
    }

    /**
     * Révoque la certification d'un émetteur.
     * PATCH /api/admin/emetteurs/{user}/revoke
     */
    public function revokeEmetteurCertification(User $user)
    {
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $user->update(['is_certified' => false]);

        return response()->json(['message' => 'Certification révoquée.', 'is_certified' => false]);
    }

    /**
     * Affiche un émetteur spécifique avec ses demandes.
     * GET /api/admin/emetteurs/{user}
     */
    public function showEmetteur(User $user)
    {
        if ($user->role !== 'emetteur') {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        return response()->json($user->load('demandesCertifications'));
    }

    /**
     * Liste tous les validateurs.
     * GET /api/admin/validateurs
     */
    public function indexValidateurs()
    {
        $validateurs = User::where('role', 'validateur')
            ->latest()
            ->get(['id', 'nom', 'prenom', 'email', 'telephone', 'is_active', 'created_at', 'last_login_at']);

        return response()->json($validateurs);
    }

    /**
     * Crée un compte validateur.
     * POST /api/admin/validateurs
     */
    public function createValidateur(Request $request)
    {
        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:100'],
            'prenom'    => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[^a-zA-Z0-9]/'],
            'telephone' => ['nullable', 'string', 'max:20'],
        ]);

        $validateur = User::create([
            'nom'       => $data['nom'],
            'prenom'    => $data['prenom'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'validateur',
            'telephone' => $data['telephone'] ?? null,
            'is_active' => true,
        ]);

        return response()->json($validateur, 201);
    }

    /**
     * Active ou désactive un validateur.
     * PATCH /api/admin/validateurs/{user}/toggle
     */
    public function toggleValidateur(User $user)
    {
        if ($user->role !== 'validateur') {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message'   => 'Compte ' . ($user->is_active ? 'activé' : 'désactivé') . ' avec succès.',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Statistiques globales pour le tableau de bord.
     * GET /api/admin/stats
     */
    public function dashboardStats()
    {
        $expireCount = Document::where('statut', 'actif')
            ->whereNotNull('date_expiration')
            ->where('date_expiration', '<', now()->toDateString())
            ->count();

        return response()->json([
            'documents' => [
                'total'    => Document::count(),
                'actifs'   => Document::where('statut', 'actif')
                    ->where(function ($q) {
                        $q->whereNull('date_expiration')
                          ->orWhere('date_expiration', '>=', now()->toDateString());
                    })->count(),
                'revoques' => Document::where('statut', 'revoque')->count(),
                'expires'  => $expireCount,
            ],
            'verifications' => [
                'total' => Verification::count(),
            ],
            'emetteurs' => [
                'total'     => User::where('role', 'emetteur')->count(),
                'certifies' => User::where('role', 'emetteur')->where('is_certified', true)->count(),
            ],
        ]);
    }
}
