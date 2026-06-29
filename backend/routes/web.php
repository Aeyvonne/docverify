<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Web
|--------------------------------------------------------------------------
|
| En développement, le frontend tourne sur Vite (port 5173) séparé.
| En production, le frontend buildé est servi par Laravel depuis /public.
|
| La route catch-all ci-dessous sert index.html pour toutes les routes
| non-API (ex: /verify/abc123 scanné depuis un téléphone), permettant
| à Vue Router de prendre en charge le routing côté client.
|
*/

// ── Tests de développement (à supprimer avant mise en production) ────
Route::get('/test-qr', function () {
    $svg = (new \App\Services\QRCodeService())->renderQrSvg('Bonjour DocVerify');
    return response($svg)->header('Content-Type', 'image/svg+xml');
});

// ── Catch-all : renvoie le frontend Vue buildé ───────────────────────
// En dev avec Vite séparé : redirige vers le port 5173
// En production : sert public/index.html (après npm run build + copie)
Route::get('/{any}', function () {
    $frontendUrl = env('FRONTEND_URL');

    // Dev : Vite tourne séparément → rediriger vers le bon port
    if ($frontendUrl && app()->environment('local')) {
        $path    = request()->path();
        $query   = request()->getQueryString();
        $fullUrl = rtrim($frontendUrl, '/') . '/' . ltrim($path, '/');
        if ($query) $fullUrl .= '?' . $query;
        return redirect($fullUrl);
    }

    // Production : servir index.html du build Vue
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response()->file($indexPath);
    }

    return response('Application frontend non trouvée.', 404);

})->where('any', '.*');
