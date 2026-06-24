<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DocumentController;
use App\Services\HashService;
use App\Services\QRCodeService;
use App\Services\PDFService;

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Auth
Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
});

// Admin
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/emetteurs',                  [AdminController::class, 'indexEmetteurs']);
    Route::post('/emetteurs',                 [AdminController::class, 'createEmetteur']);
    Route::get('/emetteurs/{user}',           [AdminController::class, 'showEmetteur']);
    Route::put('/emetteurs/{user}',           [AdminController::class, 'updateEmetteur']);
    Route::patch('/emetteurs/{user}/toggle',  [AdminController::class, 'toggleActive']);
    Route::patch('/emetteurs/{user}/certify', [AdminController::class, 'certifyEmetteur']);
    Route::patch('/emetteurs/{user}/revoke', [AdminController::class, 'revokeEmetteurCertification']);
});

// Documents
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents',  [DocumentController::class, 'index']);
});

// Test tamponnage (Postman uniquement, à supprimer en prod)
Route::post('/test-certify-upload', function (Request $request) {
    $request->validate(['document' => 'required|file|mimes:pdf|max:10240']);
    $file      = $request->file('document');
    $token     = (new QRCodeService())->generateToken();
    $verifyUrl = config('app.url') . '/verify/' . $token;
    $qrPng     = (new QRCodeService())->renderQrPng($verifyUrl);
    $certified = (new PDFService())->certifyPdf($file->getRealPath(), $qrPng);
    return response()->file($certified);
})->middleware(['auth:sanctum', 'emetteur']);
