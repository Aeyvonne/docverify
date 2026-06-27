<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Verification;
use App\Services\PDFService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;

class VerificationController extends Controller
{
    /**
     * Vérification publique d'un document via son token QR.
     * Enregistre chaque scan dans la table verifications.
     *
     * GET /api/verify/{token}
     * Aucune authentification requise.
     */
    public function verify(Request $request, string $token)
    {
        $document = Document::where('qr_token', $token)
            ->with([
                'emetteur:id,nom,prenom,nom_institution,type_institution,is_certified',
                'verifications' => fn($q) => $q->orderBy('verified_at', 'desc')->limit(10),
            ])
            ->first();

        if (! $document) {
            return response()->json([
                'message' => 'Document introuvable. Le QR code est invalide.',
            ], 404);
        }

        $statutReel   = $this->calculerStatutReel($document);
        $verification = $this->enregistrerVerification($request, $document, $statutReel);

        $pdfUrl = $document->pdf_certifie
            ? url('storage/' . $document->pdf_certifie)
            : null;

        $pdfOriginalUrl = $document->fichier_original
            ? url('storage/' . $document->fichier_original)
            : null;

        // Timeline : scan courant en tête + historique
        $timeline = collect([[
            'id'          => $verification->id,
            'statut'      => $verification->statut_au_scan,
            'ip_address'  => $verification->ip_address,
            'ville'        => $verification->ville,
            'pays'         => $verification->pays,
            'verified_at'  => $verification->verified_at->toIso8601String(),
            'est_courant'  => true,
        ]])->merge(
            $document->verifications->map(fn($v) => [
                'id'          => $v->id,
                'statut'      => $v->statut_au_scan,
                'ip_address'  => $v->ip_address,
                'ville'        => $v->ville,
                'pays'         => $v->pays,
                'verified_at'  => $v->verified_at->toIso8601String(),
                'est_courant'  => false,
            ])
        )->values();

        return response()->json([
            // Statut
            'statut'       => $statutReel,
            'statut_label' => $this->labelStatut($statutReel),
            'statut_color' => $this->colorStatut($statutReel),

            // Document
            'document' => [
                'id'               => $document->id,
                'titre'            => $document->titre,
                'type'             => $document->type,
                'hash_sha256'      => $document->hash_sha256,
                'date_emission'    => $document->date_emission?->toDateString(),
                'date_expiration'  => $document->date_expiration?->toDateString(),
                'revoked_at'       => $document->revoked_at?->toIso8601String(),
                'motif_revocation' => $document->motif_revocation,
            ],

            // Émetteur
            'emetteur' => [
                'nom'              => $document->emetteur?->nom,
                'prenom'           => $document->emetteur?->prenom,
                'nom_complet'      => $document->emetteur
                    ? $document->emetteur->prenom . ' ' . $document->emetteur->nom
                    : 'Inconnu',
                'nom_institution'  => $document->emetteur?->nom_institution,
                'type_institution' => $document->emetteur?->type_institution,
                'est_certifie'     => (bool) $document->emetteur?->is_certified,
            ],

            // Liens
            'pdf_certifie_url'  => $pdfUrl,
            'pdf_original_url'  => $pdfOriginalUrl,
            'rapport_url'       => url('/api/verify/' . $token . '/report'),

            // Scan courant
            'verification_id'     => $verification->id,
            'verifie_le'          => $verification->verified_at->toIso8601String(),
            'total_verifications' => $document->verifications()->count() + 1,

            // Timeline
            'timeline' => $timeline,
        ]);
    }

    /**
     * Vérification d'intégrité du fichier certifié.
     * Compare le SHA-256 du fichier uploadé avec celui stocké sur le serveur.
     *
     * POST /api/verify/{token}/check-integrity
     * Aucune authentification requise.
     */
    public function checkIntegrity(Request $request, string $token)
    {
        $request->validate([
            'fichier' => ['required', 'file', 'mimetypes:application/pdf'],
        ]);

        $document = Document::where('qr_token', $token)->first();

        if (! $document) {
            return response()->json([
                'message' => 'Document introuvable.',
            ], 404);
        }

        $hashUploaded  = hash_file('sha256', $request->file('fichier')->getRealPath());
        $certifiedPath = storage_path('app/public/' . $document->pdf_certifie);

        if (! file_exists($certifiedPath)) {
            return response()->json([
                'message' => 'Le fichier certifié de référence est introuvable sur le serveur.',
            ], 500);
        }

        $hashReference = hash_file('sha256', $certifiedPath);
        $integre       = hash_equals($hashReference, $hashUploaded);

        if ($integre) {
            return response()->json([
                'integre'        => true,
                'message'        => 'Le document est intègre. Les hashs correspondent.',
                'hash_fourni'    => $hashUploaded,
                'hash_reference' => $hashReference,
            ]);
        }

        return response()->json([
            'integre'        => false,
            'statut'         => 'falsifie',
            'message'        => 'Ce document a été modifié après sa certification.',
            'hash_fourni'    => $hashUploaded,
            'hash_reference' => $hashReference,
        ], 422);
    }

    /**
     * Génère et télécharge un rapport PDF de vérification.
     *
     * GET /api/verify/{token}/report
     * Aucune authentification requise.
     */
    public function downloadReport(string $token, QRCodeService $qrCodeService, PDFService $pdfService)
    {
        $document = Document::where('qr_token', $token)
            ->with('emetteur:id,nom,prenom,nom_institution,type_institution')
            ->first();

        if (! $document) {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        $statutReel  = $this->calculerStatutReel($document);
        $verifyUrl   = config('app.url') . '/verify/' . $token;
        $qrPngBinary = $qrCodeService->renderQrPng($verifyUrl, 200);
        $qrImage     = 'data:image/png;base64,' . base64_encode($qrPngBinary);

        $meta = [
            'titre'             => $document->titre,
            'type'              => $document->type,
            'statut'            => $statutReel,
            'hash_sha256'       => $document->hash_sha256,
            'date_emission'     => $document->date_emission?->toDateString(),
            'date_expiration'   => $document->date_expiration?->toDateString(),
            'motif_revocation'  => $document->motif_revocation,
            'revoked_at'        => $document->revoked_at?->toIso8601String(),
            'emetteur'          => $document->emetteur
                ? $document->emetteur->prenom . ' ' . $document->emetteur->nom
                : 'Inconnu',
            'institution'       => $document->emetteur?->nom_institution ?? 'N/A',
            'qr_token'          => $token,
            'rapport_genere_le' => now()->toIso8601String(),
            'nb_verifications'  => $document->verifications()->count(),
        ];

        $reportPath = $pdfService->generateVerificationReport($qrImage, $meta);

        return response()->download($reportPath, 'rapport_verification_' . $token . '.pdf', [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    // ── Helpers privés ────────────────────────────────────────────────

    private function calculerStatutReel(Document $document): string
    {
        if ($document->statut === 'revoque') return 'revoque';
        if ($document->date_expiration && $document->date_expiration->isPast()) return 'expire';
        return 'valide';
    }

    private function labelStatut(string $statut): string
    {
        return match ($statut) {
            'valide'  => 'Document valide',
            'revoque' => 'Document révoqué',
            'expire'  => 'Document expiré',
            default   => 'Statut inconnu',
        };
    }

    private function colorStatut(string $statut): string
    {
        return match ($statut) {
            'valide'  => 'green',
            'revoque' => 'red',
            'expire'  => 'orange',
            default   => 'gray',
        };
    }

    private function enregistrerVerification(Request $request, Document $document, string $statut): Verification
    {
        $ip    = $request->ip();
        $ville = null;
        $pays  = null;

        try {
            $position = Location::get($ip);
            if ($position) {
                $ville = $position->cityName    ?: null;
                $pays  = $position->countryName ?: null;
            }
        } catch (\Throwable $e) {
            // Géolocalisation non bloquante
        }

        return Verification::create([
            'document_id'    => $document->id,
            'ip_address'     => $ip,
            'ville'          => $ville,
            'pays'           => $pays,
            'statut_au_scan' => $statut,
            'verified_at'    => now(),
        ]);
    }
}
