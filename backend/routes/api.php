<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use App\Services\HashService;
use App\Services\QRCodeService;
use App\Services\PDFService;

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Test tamponnage sans auth (Postman uniquement)
Route::post('/test-certify-upload', function (Request $request) {
    $request->validate([
        'document' => 'required|file|mimes:pdf|max:10240',
    ]);

    $file      = $request->file('document');
    $hash      = (new HashService())->hashSha256($file);
    $token     = (new QRCodeService())->generateToken();
    $verifyUrl = config('app.url') . '/verify/' . $token;
    $qrPng     = (new QRCodeService())->renderQrPng($verifyUrl);
    $certified = (new PDFService())->certifyPdf($file->getRealPath(), $qrPng);

    return response()->file($certified);
});

// Routes documents — auth:sanctum à ajouter par le Membre 2
Route::post('/documents', [DocumentController::class, 'store']);
Route::get('/documents',  [DocumentController::class, 'index']);