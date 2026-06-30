<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DemandesCertificationController;
use App\Http\Controllers\SettingsController;

// ── Health check ──────────────────────────────────────────────────────
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// ── Authentification publique ─────────────────────────────────────────
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

// ── Authentification connecté (tous les rôles) ────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',         [AuthController::class, 'logout']);
    Route::get('/me',              [AuthController::class, 'me']);
    // Changement de mot de passe accessible à TOUS les utilisateurs connectés
    Route::put('/change-password', [AuthController::class, 'changePassword']);
});

// ── Administration (admin uniquement) ─────────────────────────────────
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats',                      [AdminController::class, 'dashboardStats']);

    Route::put('/profil',                     [AdminController::class, 'updateProfil']);
    Route::put('/password',                   [AdminController::class, 'updatePassword']);

    // SMTP
    Route::get('/smtp',                       [SettingsController::class, 'getSmtp']);
    Route::put('/smtp',                       [SettingsController::class, 'saveSmtp']);
    Route::post('/smtp/test',                 [SettingsController::class, 'testSmtp']);

    // Notifications admin
    Route::get('/notifications',              [AdminController::class, 'notifications']);
    Route::patch('/notifications/mark-read',  [AdminController::class, 'markNotificationsRead']);

    // Admins
    Route::get('/admins',                     [AdminController::class, 'indexAdmins']);
    Route::post('/admins',                    [AdminController::class, 'createAdmin']);

    // Validateurs — gérés par l'admin uniquement
    Route::get('/validateurs',                [AdminController::class, 'indexValidateurs']);
    Route::post('/validateurs',               [AdminController::class, 'createValidateur']);
    Route::patch('/validateurs/{user}/toggle',[AdminController::class, 'toggleValidateur']);

    // Émetteurs
    Route::get('/emetteurs',                  [AdminController::class, 'indexEmetteurs']);
    Route::post('/emetteurs',                 [AdminController::class, 'createEmetteur']);
    Route::get('/emetteurs/{user}',           [AdminController::class, 'showEmetteur']);
    Route::put('/emetteurs/{user}',           [AdminController::class, 'updateEmetteur']);
    Route::patch('/emetteurs/{user}/toggle',  [AdminController::class, 'toggleActive']);
    Route::patch('/emetteurs/{user}/certify', [AdminController::class, 'certifyEmetteur']);
    Route::patch('/emetteurs/{user}/revoke',  [AdminController::class, 'revokeEmetteurCertification']);
});

// ── Validateur (validateur + admin) ───────────────────────────────────
Route::middleware(['auth:sanctum', 'validateur'])->prefix('validateur')->group(function () {
    // Notifications validateur
    Route::get('/notifications',             [AdminController::class, 'notifications']);
    Route::patch('/notifications/mark-read', [AdminController::class, 'markNotificationsRead']);

    // Gestion des demandes de certification
    Route::get('/demandes',                        [DemandesCertificationController::class, 'index']);
    Route::get('/demandes/{demande}/justificatif', [DemandesCertificationController::class, 'downloadJustificatif']);
    Route::patch('/demandes/{demande}/approuver',  [DemandesCertificationController::class, 'approuver']);
    Route::patch('/demandes/{demande}/refuse',     [DemandesCertificationController::class, 'refuse']);
});

// ── Documents (émetteurs authentifiés) ────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/documents/page-dimensions',    [DocumentController::class, 'pageDimensions']);
    Route::get('/documents',                     [DocumentController::class, 'index']);
    Route::post('/documents',                    [DocumentController::class, 'store']);
    Route::patch('/documents/{document}/revoke', [DocumentController::class, 'revoke']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
});

// ── Demandes de certification (émetteurs authentifiés) ────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/demandes-certification',           [DemandesCertificationController::class, 'store']);
    Route::get('/demandes-certification/ma-demande', [DemandesCertificationController::class, 'maDemande']);
});

// ── Vérification publique ─────────────────────────────────────────────
Route::prefix('verify')->middleware('throttle:60,1')->group(function () {
    Route::get('/{token}',                  [VerificationController::class, 'verify']);
    Route::post('/{token}/check-integrity', [VerificationController::class, 'checkIntegrity']);
    Route::get('/{token}/report',           [VerificationController::class, 'downloadReport']);
});
