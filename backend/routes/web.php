<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

// Temp: API routes are defined in routes/api.php, but route:list --path=api shows none.
// To ensure API endpoints are available immediately, expose them here under /api.

Route::get('/api/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/api/documents', function () {
    return \App\Models\Document::query()->latest()->get();
});

Route::get('/api/verifications', function () {
    return \App\Models\Verification::query()->latest()->get();
});

Route::get('/api/demandes-certification', function () {
    return \App\Models\DemandesCertification::query()->latest()->get();
});

Route::get('/api/notifications', function () {
    return DB::table('notifications')->latest()->get();
});

