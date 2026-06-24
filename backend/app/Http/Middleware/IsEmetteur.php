<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsEmetteur
{
    /**
     * Autorise uniquement les utilisateurs avec role = 'emetteur'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'emetteur') {
            return response()->json(['message' => 'Accès réservé aux émetteurs.'], 403);
        }

        return $next($request);
    }
}
