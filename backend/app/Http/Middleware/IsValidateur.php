<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsValidateur
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->role;

        if (!in_array($role, ['admin', 'validateur'])) {
            return response()->json(['message' => 'Accès réservé aux validateurs et administrateurs.'], 403);
        }

        return $next($request);
    }
}
