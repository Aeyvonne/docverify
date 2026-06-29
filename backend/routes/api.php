<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\VerificationController;
use App\Services\HashService;
use App\Services\QRCodeService;
use App\Services\PDFService;

// ── Health check ──────────────────────────────────────────────────────
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// ── Authentification ──────────────────────────────────────────────────
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
});

// ── Administration (admin uniquement) ─────────────────────────────────
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Statistiques tableau de bord
    Route::get('/stats',                      [AdminController::class, 'dashboardStats']);

    // Notifications
    Route::get('/notifications',              [AdminController::class, 'notifications']);
    Route::patch('/notifications/mark-read',  [AdminController::class, 'markNotificationsRead']);

    // Gestion des admins
    Route::get('/admins',        [AdminController::class, 'indexAdmins']);
    Route::post('/admins',       [AdminController::class, 'createAdmin']);

    // Gestion des émetteurs
    Route::get('/emetteurs',                  [AdminController::class, 'indexEmetteurs']);
    Route::post('/emetteurs',                 [AdminController::class, 'createEmetteur']);
    Route::get('/emetteurs/{user}',           [AdminController::class, 'showEmetteur']);
    Route::put('/emetteurs/{user}',           [AdminController::class, 'updateEmetteur']);
    Route::patch('/emetteurs/{user}/toggle',  [AdminController::class, 'toggleActive']);
    Route::patch('/emetteurs/{user}/certify', [AdminController::class, 'certifyEmetteur']);
    Route::patch('/emetteurs/{user}/revoke',  [AdminController::class, 'revokeEmetteurCertification']);
    Route::get('/stats',                      [AdminController::class, 'dashboardStats']);
});

// ── Documents (émetteurs authentifiés) ────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/documents',                          [DocumentController::class, 'store']);
    Route::get('/documents',                           [DocumentController::class, 'index']);
    Route::patch('/documents/{document}/revoke',       [DocumentController::class, 'revoke']);
    // Retourne les dimensions (mm) de chaque page — utilisé par le frontend pour le placement QR
    Route::post('/documents/page-dimensions',          [DocumentController::class, 'pageDimensions']);
});

// Vérification publique (aucune authentification requise)
Route::prefix('verify')->group(function () {
    Route::get('/{token}',                  [VerificationController::class, 'verify']);
    Route::post('/{token}/check-integrity', [VerificationController::class, 'checkIntegrity']);
    Route::get('/{token}/report',           [VerificationController::class, 'downloadReport']);
});

// ── Demandes de certification (émetteurs authentifiés) ────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/demandes-certification',            [DemandesCertificationController::class, 'store']);
    Route::get('/demandes-certification/ma-demande',  [DemandesCertificationController::class, 'maDemande']);
});

// ── Vérification publique (aucune authentification) ───────────────────
Route::prefix('verify')->middleware('throttle:60,1')->group(function () {
    Route::get('/{token}',          [VerificationController::class, 'verify']);
    Route::get('/{token}/original', [VerificationController::class, 'streamOriginal']);
    Route::post('/{token}/check-integrity', [VerificationController::class, 'checkIntegrity']);
    Route::get('/{token}/report',   [VerificationController::class, 'downloadReport']);
});
