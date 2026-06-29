<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Notification;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Retourne les notifications non lues de l'admin connecté.
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
            'notifications'  => $notifs,
            'non_lues'       => $notifs->where('lu', false)->count(),
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
     * Liste tous les émetteurs.
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

    /**
     * Crée un nouvel administrateur (par un admin existant).
     *
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
     * Liste tous les administrateurs.
     *
     * GET /api/admin/admins
     */
    public function indexAdmins()
    {
        $admins = User::where('role', 'admin')
            ->latest()
            ->get(['id', 'nom', 'prenom', 'email', 'telephone', 'is_active', 'created_at']);

        return response()->json($admins);
    }

    /**
     * Statistiques globales pour le tableau de bord administrateur.
     *
     * GET /api/admin/stats
     */
    public function dashboardStats()
    {
        // Documents expirés = statut actif mais date_expiration dépassée
        $expireCount = Document::where('statut', 'actif')
            ->whereNotNull('date_expiration')
            ->where('date_expiration', '<', now()->toDateString())
            ->count();

        // ── Courbe : documents certifiés par mois (12 derniers mois) ──
        $docsParMois = Document::selectRaw(
            "TO_CHAR(created_at, 'YYYY-MM') as mois, COUNT(*) as total"
        )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->keyBy('mois');

        // ── Courbe : vérifications par mois (12 derniers mois) ──
        $verifParMois = Verification::selectRaw(
            "TO_CHAR(verified_at, 'YYYY-MM') as mois, COUNT(*) as total"
        )
            ->where('verified_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->keyBy('mois');

        // Générer les 12 derniers mois comme labels
        $moisLabels = [];
        $docsData   = [];
        $verifData  = [];
        for ($i = 11; $i >= 0; $i--) {
            $key          = now()->subMonths($i)->format('Y-m');
            $label        = now()->subMonths($i)->locale('fr')->isoFormat('MMM YY');
            $moisLabels[] = $label;
            $docsData[]   = $docsParMois->get($key)?->total ?? 0;
            $verifData[]  = $verifParMois->get($key)?->total ?? 0;
        }

        return response()->json([
            'documents' => [
                'total'    => Document::count(),
                'actifs'   => Document::where('statut', 'actif')
                                ->where(function ($q) {
                                    $q->whereNull('date_expiration')
                                      ->orWhere('date_expiration', '>=', now()->toDateString());
                                })
                                ->count(),
                'revoques' => Document::where('statut', 'revoque')->count(),
                'expires'  => $expireCount,
            ],
            'verifications' => [
                'total' => Verification::count(),
            ],
            'emetteurs' => [
                'total'     => User::where('role', 'emetteur')->count(),
                'certifies' => User::where('role', 'emetteur')
                                   ->where('is_certified', true)
                                   ->count(),
            ],
            // Données pour les graphiques
            'graphiques' => [
                'labels'         => $moisLabels,
                'documents_mois' => $docsData,
                'verifs_mois'    => $verifData,
            ],
        ]);
    }
}
