<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\HashService;
use App\Services\QRCodeService;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Certifie un nouveau document : hash SHA-256, génération QR, tamponnage PDF.
     * POST /api/documents
     */
    public function store(Request $request, HashService $hashService, QRCodeService $qrCodeService, PDFService $pdfService)
    {
        $validated = $request->validate([
            'titre'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'string', 'max:255'],
            'fichier_original' => ['required', 'file', 'mimetypes:application/pdf'],
            'date_emission'    => ['required', 'date'],
            'date_expiration'  => ['nullable', 'date', 'after:date_emission'],
            'qr_positions'     => ['nullable', 'string'],  // JSON: [{page,x_mm,y_mm}]
            'qr_size_mm'       => ['nullable', 'integer', 'min:15', 'max:60'],
        ]);

        $emetteur = Auth::user();

        if (!$emetteur) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        // Bloquer si l'émetteur n'est pas certifié
        if (!$emetteur->is_certified) {
            return response()->json([
                'message' => 'Votre institution doit être certifiée avant de pouvoir certifier des documents. Soumettez une demande de certification.',
            ], 403);
        }

        // Types réservés aux institutions certifiées
        $typesInstitution = ['offre_emploi', 'appel_offres', 'communique', 'decision', 'convention', 'rapport'];
        if (in_array($validated['type'], $typesInstitution) && !$emetteur->is_certified) {
            return response()->json([
                'message' => 'Votre institution doit être certifiée pour émettre ce type de document.',
            ], 403);
        }

        $uploaded = $request->file('fichier_original');

        // 1. Hash du fichier original (avant tamponnage)
        $hash = $hashService->hashSha256($uploaded);

        // 2. Vérifier doublon par hash SHA-256
        $existant = Document::where('hash_sha256', $hash)->first();
        if ($existant) {
            return response()->json([
                'message'     => 'Ce document a déjà été certifié.',
                'document_id' => $existant->id,
                'qr_token'    => $existant->qr_token,
                'certifie_le' => $existant->created_at?->toDateString(),
            ], 422);
        }

        // 3. Génération du token et du QR
        $token     = $qrCodeService->generateToken();
        $verifyUrl = config('app.frontend_url') . '/verify/' . $token;
        $qrPng     = $qrCodeService->renderQrPng($verifyUrl, 260);

        // 4. Sauvegarde du fichier original
        $filename         = Str::uuid() . '.pdf';
        $originalAbsolute = storage_path('app/originals/' . $filename);

        if (!is_dir(dirname($originalAbsolute))) {
            mkdir(dirname($originalAbsolute), 0755, true);
        }
        $uploaded->move(dirname($originalAbsolute), $filename);
        $originalPath = 'originals/' . $filename;

        // 5. Tamponnage PDF
        $qrPositions = [];
        if (!empty($validated['qr_positions'])) {
            $decoded = json_decode($validated['qr_positions'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $p) {
                    if (isset($p['page']) && is_numeric($p['page'])) {
                        $qrPositions[(int)$p['page']] = [
                            'x_mm' => isset($p['x_mm']) && is_numeric($p['x_mm']) ? (float)$p['x_mm'] : null,
                            'y_mm' => isset($p['y_mm']) && is_numeric($p['y_mm']) ? (float)$p['y_mm'] : null,
                        ];
                    }
                }
            }
        }

        $pdfCertifiePath = $pdfService->certifyPdf(
            $originalAbsolute,
            $qrPng,
            qrSizeMm:    isset($validated['qr_size_mm']) ? (int)$validated['qr_size_mm'] : 25,
            qrPositions: $qrPositions,
        );

        $pdfCertifieRelative = str_replace('\\', '/', str_replace(
            storage_path('app/public') . DIRECTORY_SEPARATOR,
            '',
            str_replace(storage_path('app/public') . '/', '', $pdfCertifiePath)
        ));

        // 6. Persistance dans une transaction pour éviter les fichiers orphelins
        $document = DB::transaction(function () use ($emetteur, $validated, $originalPath, $hash, $token, $pdfCertifieRelative) {
            return Document::create([
                'emetteur_id'      => $emetteur->id,
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
                'qr_position_x'    => $qrPositions[1]['x_mm'] ?? null,
                'qr_position_y'    => $qrPositions[1]['y_mm'] ?? null,
            ]);
        });

        return response()->json($document->load('verifications'), 201);
    }

    /**
     * Retourne les dimensions (en mm) de chaque page d'un PDF uploadé.
     * POST /api/documents/page-dimensions
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

            $pages = [];
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplId  = $fpdi->importPage($i);
                $size   = $fpdi->getTemplateSize($tplId);
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

    /**
     * Liste les documents de l'émetteur connecté.
     * GET /api/documents
     */
    public function index(Request $request)
    {
        $emetteur = Auth::user();
        if (!$emetteur) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $documents = Document::where('emetteur_id', $emetteur->id)
            ->withCount('verifications')
            ->latest()
            ->get();

        return response()->json($documents);
    }

    /**
     * Télécharge le PDF certifié d'un document.
     * GET /api/documents/{document}/download
     */
    public function download(Request $request, Document $document)
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && $document->emetteur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $path = storage_path('app/public/' . $document->pdf_certifie);

        if (!file_exists($path)) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        return response()->download($path, $document->titre . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Révoque un document avec un motif obligatoire.
     * PATCH /api/documents/{document}/revoke
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
}
