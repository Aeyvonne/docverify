<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Models\Verification;
use App\Models\DemandesCertification;
use App\Models\Notification;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/documents', function () {
    return Document::query()->latest()->get();
});

Route::get('/verifications', function () {
    return Verification::query()->latest()->get();
});

Route::get('/demandes-certification', function () {
    return DemandesCertification::query()->latest()->get();
});

Route::get('/notifications', function () {
    return DB::table('notifications')->latest()->get();
});


