<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Verification;
use App\Services\HashService;
use App\Services\PDFService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Stevebauman\Location\Facades\Location;

class VerificationController extends Controller
{
    /**
     * Vérification publique d'un document via son token QR.
     *
     * GET /api/verify/{token}
     * Aucune authentification requise.
     */
    public function verify(Request $request, string $token)
    {
        $document = Document::where('qr_token', $token)
            ->with([
                'emetteur:id,nom,prenom,nom_institution,type_institution,is_certified',
                // Les 10 dernières vérifications pour la timeline (du plus récent au plus ancien)
                'verifications' => function ($q) {
                    $q->orderBy('verified_at', 'desc')->limit(10);
                },
            ])
            ->first();

        if (! $document) {
            return response()->json([
                'message' => 'Document introuvable. Le QR code est invalide.',
            ], 404);
        }

        // Calcul du statut réel au moment du scan
        $statutReel = $this->calculerStatutReel($document);

        // Enregistrement de la vérification courante
        $verification = $this->enregistrerVerification($request, $document, $statutReel);

        // URL publique du PDF certifié
        $pdfUrl = $document->pdf_certifie
            ? url('storage/' . $document->pdf_certifie)
            : null;

        // Timeline : le scan courant + les scans précédents chargés
        $timeline = collect([
            [
                'id'          => $verification->id,
                'statut'      => $verification->statut_au_scan,
                'ip_address'  => $verification->ip_address,
                'ville'       => $verification->ville,
                'pays'        => $verification->pays,
                'verified_at' => $verification->verified_at->toIso8601String(),
                'est_courant' => true,
            ],
        ])->merge(
            $document->verifications->map(fn($v) => [
                'id'          => $v->id,
                'statut'      => $v->statut_au_scan,
                'ip_address'  => $v->ip_address,
                'ville'       => $v->ville,
                'pays'        => $v->pays,
                'verified_at' => $v->verified_at->toIso8601String(),
                'est_courant' => false,
            ])
        )->values();

        return response()->json([
            // ── Statut global ──────────────────────────────────────────────
            'statut'      => $statutReel,
            'statut_label'=> $this->labelStatut($statutReel),
            'statut_color'=> $this->colorStatut($statutReel),

            // ── Infos du document ──────────────────────────────────────────
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

            // ── Infos de l'émetteur ────────────────────────────────────────
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

            // ── PDF certifié ───────────────────────────────────────────────
            'pdf_certifie_url'    => $pdfUrl,
            'rapport_url'         => url('/api/verify/' . $token . '/report'),

            // ── Scan courant ───────────────────────────────────────────────
            'verification_id'     => $verification->id,
            'verifie_le'          => $verification->verified_at->toIso8601String(),

            // ── Timeline des scans ─────────────────────────────────────────
            'total_verifications' => $document->verifications()->count() + 1,
            'timeline'            => $timeline,
        ]);
    }

    /**
     * Vérification d'intégrité : compare le hash SHA-256 du fichier uploadé
     * avec le hash du PDF certifié stocké sur le serveur.
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
                'message' => 'Document introuvable. Le QR code est invalide.',
            ], 404);
        }

        // Hash du fichier uploadé par le vérificateur
        $uploadedFile   = $request->file('fichier');
        $hashUploaded   = hash_file('sha256', $uploadedFile->getRealPath());

        // Résoudre le chemin certifié de façon sécurisée (éviter path traversal)
        $storageBase    = realpath(storage_path('app/public'));
        $certifiedPath  = realpath(storage_path('app/public/' . $document->pdf_certifie));

        if ($certifiedPath === false || !str_starts_with($certifiedPath, $storageBase . DIRECTORY_SEPARATOR)) {
            return response()->json(['message' => 'Chemin de fichier invalide.'], 500);
        }

        if (! file_exists($certifiedPath)) {
            return response()->json([
                'message' => 'Le fichier certifié de référence est introuvable sur le serveur.',
            ], 500);
        }

        $hashReference = hash_file('sha256', $certifiedPath);

        $integre = hash_equals($hashReference, $hashUploaded);

        if ($integre) {
            return response()->json([
                'integre'         => true,
                'message'         => 'Le document est intègre. Les hashs correspondent.',
                'hash_fourni'     => $hashUploaded,
                'hash_reference'  => $hashReference,
            ]);
        }

        return response()->json([
            'integre'         => false,
            'statut'          => 'falsifie',
            'message'         => 'Attention : ce document a été modifié après sa certification. Les hashs ne correspondent pas.',
            'hash_fourni'     => $hashUploaded,
            'hash_reference'  => $hashReference,
        ], 422);
    }

    /**
     * Génère et télécharge un rapport PDF de vérification.
     *
     * GET /api/verify/{token}/report
     * Aucune authentification requise.
     */
    public function downloadReport(
        string $token,
        QRCodeService $qrCodeService,
        PDFService $pdfService
    ) {
        $document = Document::where('qr_token', $token)
            ->with('emetteur:id,nom,prenom,nom_institution,type_institution')
            ->first();

        if (! $document) {
            return response()->json([
                'message' => 'Document introuvable. Le QR code est invalide.',
            ], 404);
        }

        $statutReel = $this->calculerStatutReel($document);

        // Génération du QR en PNG base64 (DomPDF ne supporte pas SVG en <img>)
        $verifyUrl = config('app.url') . '/verify/' . $token;
        $qrPngBinary = $qrCodeService->renderQrPng($verifyUrl, 200);
        $qrSvg       = 'data:image/png;base64,' . base64_encode($qrPngBinary);

        // Métadonnées transmises à PDFService@generateVerificationReport
        $meta = [
            'titre'            => $document->titre,
            'type'             => $document->type,
            'statut'           => $statutReel,
            'hash_sha256'      => $document->hash_sha256,
            'date_emission'    => $document->date_emission?->toDateString(),
            'date_expiration'  => $document->date_expiration?->toDateString(),
            'motif_revocation' => $document->motif_revocation,
            'revoked_at'       => $document->revoked_at?->toIso8601String(),
            'emetteur'         => $document->emetteur
                ? $document->emetteur->nom . ' ' . $document->emetteur->prenom
                : 'Inconnu',
            'institution'      => $document->emetteur?->nom_institution ?? 'N/A',
            'qr_token'         => $token,
            'rapport_genere_le'=> now()->toIso8601String(),
            'nb_verifications' => $document->verifications()->count(),
        ];

        $reportPath = $pdfService->generateVerificationReport($qrSvg, $meta);

        return response()->download($reportPath, 'rapport_verification_' . $token . '.pdf', [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    /**
     * Calcule le statut réel du document au moment du scan.
     * Priorité : révoqué > expiré > actif.
     */
    private function calculerStatutReel(Document $document): string
    {
        if ($document->statut === 'revoque') {
            return 'revoque';
        }

        if ($document->date_expiration && $document->date_expiration->isPast()) {
            return 'expire';
        }

        return 'valide';
    }

    /**
     * Libellé lisible du statut — utilisé directement par le frontend.
     */
    private function labelStatut(string $statut): string
    {
        return match ($statut) {
            'valide'  => 'Document valide',
            'revoque' => 'Document révoqué',
            'expire'  => 'Document expiré',
            default   => 'Statut inconnu',
        };
    }

    /**
     * Couleur sémantique du statut — convention Tailwind/CSS pour le frontend.
     * Le frontend peut utiliser directement cette valeur pour la classe CSS ou la couleur du badge.
     */
    private function colorStatut(string $statut): string
    {
        return match ($statut) {
            'valide'  => 'green',
            'revoque' => 'red',
            'expire'  => 'orange',
            default   => 'gray',
        };
    }

    /**
     * Enregistre la vérification en base avec IP, ville, pays et horodatage.
     */
    private function enregistrerVerification(Request $request, Document $document, string $statut): Verification
    {
        $ip   = $request->ip();
        $ville = null;
        $pays  = null;

        // Géolocalisation via stevebauman/location
        // En local (127.0.0.1), Location::get() peut retourner null ou des données vides
        try {
            $position = Location::get($ip);
            if ($position) {
                $ville = $position->cityName    ?: null;
                $pays  = $position->countryName ?: null;
            }
        } catch (\Throwable $e) {
            // Géolocalisation non bloquante — on continue sans ville/pays
        }

        return Verification::create([
            'document_id'   => $document->id,
            'ip_address'    => $ip,
            'ville'         => $ville,
            'pays'          => $pays,
            'statut_au_scan'=> $statut,
            'verified_at'   => now(),
        ]);
    }
}
