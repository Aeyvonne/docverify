<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Verification;
use App\Services\HashService;
use App\Services\QRCodeService;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function store(Request $request, HashService $hashService, QRCodeService $qrCodeService, PDFService $pdfService)
    {
        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'fichier_original' => ['required', 'file', 'mimetypes:application/pdf'],
            'date_emission' => ['required', 'date'],
            'date_expiration' => ['nullable', 'date'],
        ]);

        $uploaded = $validated['fichier_original'];
        $hash = $hashService->hashSha256($uploaded);

        $token = $qrCodeService->generateToken();
        $qrValue = (string) $token; // à adapter si vous voulez un lien complet
        $qrSvg = $qrCodeService->renderQrSvg($qrValue, 260);

        $originalPath = $uploaded->storeAs('originals', Str::uuid() . '.pdf', 'local');
        $originalAbsolute = storage_path('app/' . $originalPath);

        $meta = [
            'titre' => $validated['titre'],
            'type' => $validated['type'],
            'hash_sha256' => $hash,
            'qr_token' => $token,
            'date_emission' => $validated['date_emission'],
            'date_expiration' => $validated['date_expiration'] ?? null,
            'statut' => 'actif',
        ];

        $pdfCertifiePath = $pdfService->generateCertifiedPdfFromHtml($originalAbsolute, $qrSvg, $meta);
        $pdfCertifieRelative = str_replace(storage_path('app/') . DIRECTORY_SEPARATOR, '', $pdfCertifiePath);

        // NOTE: absence d’auth confirmée dans la conversation précédente.
        // On met pour l’instant emetteur_id = utilisateur connecté si disponible, sinon NULL échouera sur FK.
        $emetteurId = null;
        try {
            $emetteurId = auth()->id();
        } catch (\Throwable $e) {
        }

        if (!$emetteurId) {
            return response()->json(['message' => 'auth non configurée : impossible de déterminer emetteur_id'], 422);
        }

        $document = Document::create([
            'emetteur_id' => $emetteurId,
            'titre' => $validated['titre'],
            'type' => $validated['type'],
            'fichier_original' => $originalPath,
            'hash_sha256' => $hash,
            'qr_token' => $token,
            'pdf_certifie' => $pdfCertifieRelative,
            'statut' => 'actif',
            'motif_revocation' => null,
            'pin_hash' => null,
            'date_emission' => $validated['date_emission'],
            'date_expiration' => $validated['date_expiration'] ?? null,
            'revoked_at' => null,
        ]);

        return response()->json($document->load('verifications'), 201);
    }

    public function index(Request $request)
    {
        $emetteurId = auth()->id();
        if (!$emetteurId) {
            return response()->json(['message' => 'auth non configurée'], 422);
        }

        $documents = Document::query()
            ->where('emetteur_id', $emetteurId)
            ->latest()
            ->get();

        return response()->json($documents);
    }
}

