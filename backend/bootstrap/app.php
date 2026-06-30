<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enregistrement des alias de middleware
        $middleware->alias([
            'admin'      => \App\Http\Middleware\IsAdmin::class,
            'emetteur'   => \App\Http\Middleware\IsEmetteur::class,
            'validateur' => \App\Http\Middleware\IsValidateur::class,
        ]);

        // Sanctum gère l'auth via tokens pour les routes API
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();