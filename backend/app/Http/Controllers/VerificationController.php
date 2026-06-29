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
            ? url('/api/verify/' . $token . '/original')
            : null;

        // Timeline : scan courant en tête + historique
        $timeline = collect([[
            'id'          => $verification->id,
            'statut'      => $verification->statut_au_scan,
            'ip_address'  => $this->maskIp($verification->ip_address),
            'ville'        => $verification->ville,
            'pays'         => $verification->pays,
            'verified_at'  => $verification->verified_at->toIso8601String(),
            'est_courant'  => true,
        ]])->merge(
            $document->verifications->map(fn($v) => [
                'id'          => $v->id,
                'statut'      => $v->statut_au_scan,
                'ip_address'  => $this->maskIp($v->ip_address),
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

        // Confiner le chemin certifié dans storage/app/public/
        $allowedBase   = realpath(storage_path('app/public'));
        $certifiedPath = realpath(storage_path('app/public/' . $document->pdf_certifie));

        if ($certifiedPath === false || $allowedBase === false
            || !str_starts_with($certifiedPath, $allowedBase . DIRECTORY_SEPARATOR)) {
            \Illuminate\Support\Facades\Log::error('checkIntegrity: chemin invalide', ['document_id' => $document->id]);
            return response()->json(['message' => 'Une erreur est survenue lors de la vérification. Veuillez réessayer.'], 500);
        }

        if (! file_exists($certifiedPath)) {
            \Illuminate\Support\Facades\Log::error('checkIntegrity: fichier certifié introuvable', ['document_id' => $document->id]);
            return response()->json([
                'message' => 'Une erreur est survenue lors de la vérification. Veuillez réessayer.',
            ], 500);
        }

        $hashReference = hash_file('sha256', $certifiedPath);
        $integre       = hash_equals($hashReference, $hashUploaded);

        if ($integre) {
            return response()->json([
                'integre' => true,
                'message' => 'Le document est intègre. Les hashs correspondent.',
            ]);
        }

        return response()->json([
            'integre' => false,
            'statut'  => 'falsifie',
            'message' => 'Ce document a été modifié après sa certification.',
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

    /**
     * Sert le fichier PDF original en streaming public.
     * Le fichier est dans storage/app/originals/ (privé) donc inaccessible
     * via une URL directe — cette route le sert via Laravel.
     *
     * GET /api/verify/{token}/original
     * Aucune authentification requise.
     */
    public function streamOriginal(string $token)
    {
        $document = Document::where('qr_token', $token)->first();

        if (! $document || ! $document->fichier_original) {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        // Ne pas servir le fichier si le document est révoqué
        if ($document->statut === 'revoque') {
            return response()->json(['message' => 'Ce document a été révoqué et n\'est plus accessible.'], 403);
        }

        // Confiner dans storage/app/originals/
        $allowedOrigBase = realpath(storage_path('app/originals'));
        $realOrigPath    = realpath(storage_path('app/' . $document->fichier_original));

        if ($realOrigPath === false || $allowedOrigBase === false
            || !str_starts_with($realOrigPath, $allowedOrigBase . DIRECTORY_SEPARATOR)) {
            return response()->json(['message' => 'Fichier introuvable sur le serveur.'], 404);
        }

        if (! file_exists($realOrigPath)) {
            return response()->json(['message' => 'Fichier introuvable sur le serveur.'], 404);
        }

        return response()->file($realOrigPath, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document_original.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function maskIp(string $ip): string
    {
        // IPv4 : masquer le dernier octet (ex: 192.168.1.x)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\d+$/', 'x', $ip);
        }
        // IPv6 : masquer les 4 derniers groupes
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . ':x:x:x:x';
        }
        return 'x.x.x.x';
    }

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
