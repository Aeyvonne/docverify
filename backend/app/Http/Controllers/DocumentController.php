<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\HashService;
use App\Services\QRCodeService;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DocumentController extends Controller
{
    /**
     * Certifie un nouveau document : hash SHA-256, génération QR, tamponnage PDF.
     *
     * POST /api/documents
     * Protégé : auth:sanctum
     */
    public function store(Request $request, HashService $hashService, QRCodeService $qrCodeService, PDFService $pdfService)
    {
        $validated = $request->validate([
            'titre'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'string', 'max:255'],
            'fichier_original' => ['required', 'file', 'mimetypes:application/pdf'],
            'date_emission'    => ['required', 'date'],
            'date_expiration'  => ['nullable', 'date', 'after:date_emission'],
            // Position du QR en mm — max 1200 pour couvrir les grands formats (A0 = 1189mm)
            'qr_position_x'    => ['nullable', 'numeric', 'min:0', 'max:1200'],
            'qr_position_y'    => ['nullable', 'numeric', 'min:0', 'max:1200'],
            // Taille du QR en mm : entre 15 (discret) et 60 (très visible), défaut 25
            'qr_size_mm'       => ['nullable', 'integer', 'min:15', 'max:60'],
        ]);

        $emetteur = Auth::user();
        if (! $emetteur) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        // Types réservés aux institutions certifiées (jamais accessibles aux particuliers)
        $typesInstitution = ['offre_emploi', 'appel_offres', 'communique', 'decision', 'convention', 'rapport'];
        $isParticulier    = $emetteur->type_institution === 'particulier';

        if (in_array($validated['type'], $typesInstitution)) {
            if ($isParticulier) {
                return response()->json([
                    'message' => 'Les particuliers ne peuvent pas émettre ce type de document.',
                ], 403);
            }
            if (! $emetteur->is_certified) {
                return response()->json([
                    'message' => 'Votre institution doit être certifiée pour émettre ce type de document.',
                ], 403);
            }
        }

        $emetteurId = $emetteur->id;

        $uploaded = $request->file('fichier_original');

        // 1. Hash du fichier original (avant tamponnage)
        $hash = $hashService->hashSha256($uploaded);

        // Vérifier que ce document n'a pas déjà été certifié (doublon par hash SHA-256)
        $existant = Document::where('hash_sha256', $hash)->first();
        if ($existant) {
            return response()->json([
                'message'    => 'Ce document a déjà été certifié.',
                'document_id' => $existant->id,
                'qr_token'   => $existant->qr_token,
                'certifie_le'=> $existant->created_at?->toDateString(),
            ], 422);
        }

        // 2. Génération du token et du QR
        $token     = $qrCodeService->generateToken();
        // Le QR pointe vers le FRONTEND (Vue Router gère /verify/:token)
        $verifyUrl = env('FRONTEND_URL', config('app.url')) . '/verify/' . $token;
        $qrPng     = $qrCodeService->renderQrPng($verifyUrl, 260);

        // 3. Sauvegarde du fichier original
        $filename         = Str::uuid() . '.pdf';
        $originalAbsolute = storage_path('app/originals/' . $filename);

        if (! is_dir(dirname($originalAbsolute))) {
            mkdir(dirname($originalAbsolute), 0777, true);
        }
        $uploaded->move(dirname($originalAbsolute), $filename);
        $originalPath = 'originals/' . $filename;

        // 4. Tamponnage PDF avec taille, position libre ou automatique
        $qrSizeMm = isset($validated['qr_size_mm']) ? (int) $validated['qr_size_mm'] : 25;

        $pdfCertifiePath = $pdfService->certifyPdf(
            $originalAbsolute,
            $qrPng,
            qrSizeMm:  $qrSizeMm,
            positionX: isset($validated['qr_position_x']) ? (float) $validated['qr_position_x'] : null,
            positionY: isset($validated['qr_position_y']) ? (float) $validated['qr_position_y'] : null,
        );

        $pdfCertifieRelative = str_replace(
            [storage_path('app/public') . DIRECTORY_SEPARATOR, storage_path('app/public') . '/'],
            '',
            $pdfCertifiePath
        );
        // Normaliser en slashes forward pour la portabilité
        $pdfCertifieRelative = str_replace('\\', '/', $pdfCertifieRelative);

        // 5. Persistance
        $document = Document::create([
            'emetteur_id'      => $emetteurId,
            'titre'            => $validated['titre'],
            'type'             => $validated['type'],
            'fichier_original' => $originalPath,
            'hash_sha256'      => $hash,
            'qr_token'         => $token,
            'pdf_certifie'     => $pdfCertifieRelative,
            'statut'           => 'actif',
            'motif_revocation' => null,
            'pin_hash'         => null,
            'date_emission'    => $validated['date_emission'],
            'date_expiration'  => $validated['date_expiration'] ?? null,
            'revoked_at'       => null,
            'qr_position_x'    => isset($validated['qr_position_x']) ? (float) $validated['qr_position_x'] : null,
            'qr_position_y'    => isset($validated['qr_position_y']) ? (float) $validated['qr_position_y'] : null,
            'qr_size_mm'       => $qrSizeMm,
        ]);

        return response()->json($document->load('verifications'), 201);
    }

    /**
     * Liste les documents de l'émetteur connecté.
     *
     * GET /api/documents
     * Protégé : auth:sanctum
     */
    public function index()
    {
        $emetteurId = Auth::id();
        if (! $emetteurId) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $documents = Document::where('emetteur_id', $emetteurId)
            ->withCount('verifications')
            ->latest()
            ->get();

        return response()->json($documents);
    }

    /**
     * Révoque un document avec motif obligatoire.
     *
     * PATCH /api/documents/{document}/revoke
     * Protégé : auth:sanctum
     */
    public function revoke(Request $request, Document $document)
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && $document->emetteur_id !== $user->id) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à révoquer ce document.'], 403);
        }

        if ($document->statut === 'revoque') {
            return response()->json(['message' => 'Ce document est déjà révoqué.'], 422);
        }

        $validated = $request->validate([
            'motif_revocation' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $document->update([
            'statut'           => 'revoque',
            'motif_revocation' => $validated['motif_revocation'],
            'revoked_at'       => now(),
            'revoked_by'       => $user->id,
        ]);

        return response()->json([
            'message'  => 'Document révoqué avec succès.',
            'document' => $document->fresh(),
        ]);
    }

    /**
     * Télécharge le PDF certifié d'un document.
     * Seul l'émetteur propriétaire ou un admin peut télécharger.
     *
     * GET /api/documents/{document}/download
     * Protégé : auth:sanctum
     */
    public function download(Document $document)
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && $document->emetteur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $stored = $document->pdf_certifie;

        // La colonne peut contenir soit un chemin absolu, soit un chemin relatif
        // selon la version du code qui a créé le document.
        if (Str::startsWith($stored, [storage_path(), 'C:\\', 'C:/'])) {
            // Chemin absolu stocké directement → normaliser les séparateurs
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $stored);
        } else {
            // Chemin relatif → préfixer avec storage/app/public/
            $path = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim(
                str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $stored),
                DIRECTORY_SEPARATOR
            ));
        }

        if (! file_exists($path)) {
            return response()->json([
                'message' => 'Fichier introuvable. Chemin : ' . $path,
            ], 404);
        }

        $filename = 'DocVerify_' . Str::slug($document->titre) . '.pdf';

        return response()->download($path, $filename, [
            'Content-Type'              => 'application/pdf',
            'Access-Control-Allow-Origin' => config('cors.allowed_origins')[0] ?? '*',
        ]);
    }

    /**
     * Retourne les dimensions (en mm) de chaque page d'un PDF uploadé.
     * Utilisé par le frontend pour convertir les coordonnées pixel → mm.
     *
     * POST /api/documents/page-dimensions
     * Protégé : auth:sanctum
     */
    public function pageDimensions(Request $request)
    {
        $request->validate([
            'fichier' => ['required', 'file', 'mimetypes:application/pdf'],
        ]);

        $path = $request->file('fichier')->getRealPath();

        try {
            $fpdi      = new \setasign\Fpdi\Fpdi();
            $pageCount = $fpdi->setSourceFile($path);
            $pages     = [];

            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId = $fpdi->importPage($i);
                $size  = $fpdi->getTemplateSize($tplId);
                $pages[] = [
                    'page'        => $i,
                    'width_mm'    => round($size['width'],  2),
                    'height_mm'   => round($size['height'], 2),
                    'orientation' => $size['orientation'],
                ];
            }
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Impossible de lire le fichier PDF : ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'total_pages' => $pageCount,
            'pages'       => $pages,
        ]);
    }
}
