<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\HashService;
use App\Services\QRCodeService;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
        ]);

        // ✅ CORRECTION : récupérer le fichier depuis $request, pas depuis $validated
        $uploaded = $request->file('fichier_original');

        $hash = $hashService->hashSha256($uploaded);

        $token     = $qrCodeService->generateToken();
        $verifyUrl = config('app.url') . '/verify/' . $token;
        $qrPng     = $qrCodeService->renderQrPng($verifyUrl, 260);

        $originalPath     = $uploaded->storeAs('originals', Str::uuid() . '.pdf', 'local');
        $originalAbsolute = storage_path('app/' . $originalPath);

        $pdfCertifiePath     = $pdfService->certifyPdf($originalAbsolute, $qrPng);
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
        ]);

        return response()->json($document->load('verifications'), 201);
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
}