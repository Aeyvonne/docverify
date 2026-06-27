<?php

namespace App\Http\Controllers;

use App\Models\DemandesCertification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DemandesCertificationController extends Controller
{
    /**
     * Soumet une nouvelle demande de certification.
     * L'émetteur fournit son NINEA, RCCM et un fichier justificatif.
     *
     * POST /api/demandes-certification
     * Protégé : auth:sanctum
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Un utilisateur déjà certifié ne peut pas resoumettre
        if ($user->is_certified) {
            return response()->json([
                'message' => 'Votre compte est déjà certifié.',
            ], 422);
        }

        // Les particuliers ne peuvent pas demander une certification institutionnelle
        if ($user->type_institution === 'particulier') {
            return response()->json([
                'message' => 'Les comptes particuliers ne peuvent pas soumettre de demande de certification.',
            ], 403);
        }

        // Bloquer si une demande est déjà en attente
        $demandeEnCours = DemandesCertification::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->exists();

        if ($demandeEnCours) {
            return response()->json([
                'message' => 'Vous avez déjà une demande en attente de traitement.',
            ], 422);
        }

        $validated = $request->validate([
            'ninea'          => ['required', 'string', 'max:50'],
            'rccm'           => ['required', 'string', 'max:50'],
            'fichier_preuve' => ['required', 'file', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:5120'],
            'message'        => ['nullable', 'string', 'max:1000'],
        ]);

        // Stockage du fichier justificatif
        $file     = $request->file('fichier_preuve');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('preuves_certification', $filename, 'local');

        $demande = DemandesCertification::create([
            'user_id'        => $user->id,
            'ninea'          => $validated['ninea'],
            'rccm'           => $validated['rccm'],
            'fichier_preuve' => $path,
            'statut'         => 'en_attente',
        ]);

        return response()->json([
            'message' => 'Demande de certification soumise avec succès. Elle sera traitée par un administrateur.',
            'demande' => $demande,
        ], 201);
    }

    /**
     * Retourne la dernière demande de l'utilisateur connecté.
     *
     * GET /api/demandes-certification/ma-demande
     * Protégé : auth:sanctum
     */
    public function maDemande(Request $request)
    {
        $demande = DemandesCertification::where('user_id', $request->user()->id)
            ->latest()
            ->first();

        return response()->json($demande);
    }

    /**
     * Liste toutes les demandes en attente — admin uniquement.
     *
     * GET /api/admin/demandes
     * Protégé : auth:sanctum + admin
     */
    public function index()
    {
        $demandes = DemandesCertification::with('user:id,nom,prenom,email,nom_institution')
            ->orderByRaw("CASE statut WHEN 'en_attente' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        return response()->json($demandes);
    }

    /**
     * Refuse une demande avec un motif obligatoire.
     *
     * PATCH /api/admin/demandes/{demande}/refuse
     * Protégé : auth:sanctum + admin
     */
    public function refuse(Request $request, DemandesCertification $demande)
    {
        if ($demande->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $request->validate([
            'motif_refus' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $demande->update([
            'statut'      => 'refusee',
            'motif_refus' => $request->motif_refus,
            'traite_par'  => $request->user()->id,
            'traite_le'   => now(),
        ]);

        return response()->json([
            'message' => 'Demande refusée.',
            'demande' => $demande->fresh(),
        ]);
    }
}
