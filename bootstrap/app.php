<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            if (app()->environment('local', 'testing')) {
                Route::middleware('web')->group(base_path('routes/debug.php'));
                Route::middleware('web')->group(base_path('routes/test-auth.php'));
            }
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Excluir cookies de autenticación de intra.orpro.cl de la encriptación
        $middleware->encryptCookies(except: [
            'AUTHOMNIC',
            'AUTHGESCON',
        ]);
        
        $middleware->alias([
            'centralized.auth' => \App\Http\Middleware\CentralizedAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
