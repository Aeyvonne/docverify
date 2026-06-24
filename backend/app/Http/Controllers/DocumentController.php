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
    public function store(Request $request, HashService $hashService, QRCodeService $qrCodeService, PDFService $pdfService)
    {
        $validated = $request->validate([
            'titre'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'string', 'max:255'],
            'fichier_original' => ['required', 'file', 'mimetypes:application/pdf'],
            'date_emission'    => ['required', 'date'],
            'date_expiration'  => ['nullable', 'date'],
            // Position du QR en mm. null = automatique (coin bas-droit).
            // Valeurs typiques pour une page A4 (210×297 mm) : x entre 0 et 185, y entre 0 et 272
            'qr_position_x'   => ['nullable', 'numeric', 'min:0', 'max:500'],
            'qr_position_y'   => ['nullable', 'numeric', 'min:0', 'max:500'],
        ]);

        // ✅ CORRECTION : récupérer le fichier depuis $request, pas depuis $validated
        $uploaded = $request->file('fichier_original');

        $hash = $hashService->hashSha256($uploaded);

        $token     = $qrCodeService->generateToken();
        $verifyUrl = config('app.url') . '/verify/' . $token;
        $qrPng     = $qrCodeService->renderQrPng($verifyUrl, 260);

        $filename         = Str::uuid() . '.pdf';
        $originalAbsolute = storage_path('app' . DIRECTORY_SEPARATOR . 'originals' . DIRECTORY_SEPARATOR . $filename);

        if (!is_dir(dirname($originalAbsolute))) {
          mkdir(dirname($originalAbsolute), 0777, true);
}

        $uploaded->move(dirname($originalAbsolute), $filename);
        $originalPath = 'originals' . DIRECTORY_SEPARATOR . $filename;

        $pdfCertifiePath     = $pdfService->certifyPdf(
            $originalAbsolute,
            $qrPng,
            positionX: isset($validated['qr_position_x']) ? (float) $validated['qr_position_x'] : null,
            positionY: isset($validated['qr_position_y']) ? (float) $validated['qr_position_y'] : null,
        );
        $pdfCertifieRelative = str_replace(storage_path('app/') . DIRECTORY_SEPARATOR, '', $pdfCertifiePath);

        $emetteurId = Auth::id();

        if (!$emetteurId) {
            return response()->json([
                'message' => 'Non authentifié : impossible de déterminer emetteur_id.',
            ], 401);
        }

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
            'qr_position_x'   => isset($validated['qr_position_x']) ? (float) $validated['qr_position_x'] : null,
            'qr_position_y'   => isset($validated['qr_position_y']) ? (float) $validated['qr_position_y'] : null,
        ]);

        return response()->json($document->load('verifications'), 201);
    }

    /**
     * Retourne les dimensions (en mm) de chaque page d'un PDF uploadé.
     * Utilisé par le frontend pour convertir les coordonnées pixel → mm
     * avant de soumettre qr_position_x / qr_position_y.
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
            $fpdi = new \setasign\Fpdi\Fpdi();
            $pageCount = $fpdi->setSourceFile($path);

            $pages = [];
            for ($i = 1; $i <= $pageCount; $i++) {
                $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($fpdi->importPage($i));
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

    public function index(Request $request)
    {
        $emetteurId = Auth::id();

        if (!$emetteurId) {
            return response()->json([
                'message' => 'Non authentifié.',
            ], 401);
        }

        $documents = Document::query()
            ->where('emetteur_id', $emetteurId)
            ->latest()
            ->get();

        return response()->json($documents);
    }

    /**
     * Révoque un document avec un motif obligatoire.
     *
     * PATCH /api/documents/{document}/revoke
     * Protégé : auth:sanctum
     * Seul l'émetteur du document ou un admin peut révoquer.
     */
    public function revoke(Request $request, Document $document)
    {
        $user = Auth::user();

        // Vérification des droits : émetteur propriétaire ou admin
        if ($user->role !== 'admin' && $document->emetteur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à révoquer ce document.',
            ], 403);
        }

        if ($document->statut === 'revoque') {
            return response()->json([
                'message' => 'Ce document est déjà révoqué.',
            ], 422);
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
            'message'          => 'Document révoqué avec succès.',
            'document'         => $document->fresh(),
        ]);
    }
}