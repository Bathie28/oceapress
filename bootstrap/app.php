<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['webhooks/wave']);
        $middleware->alias([
            'role' => \App\Http\Middleware\VerifierRole::class,
            'mot-de-passe' => \App\Http\Middleware\ForcerChangementMotDePasse::class,
        ]);
        $middleware->redirectUsersTo(function (Request $request): string {
            try {
                return route(app(\App\Http\Controllers\AuthController::class)->espaceDe($request->user()));
            } catch (\RuntimeException) {
                return '/';
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
