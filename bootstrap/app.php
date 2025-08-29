<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware de seguridad para APIs
        $middleware->api([
            \App\Http\Middleware\LogRequests::class,
            \App\Http\Middleware\SanitizeInput::class,
        ]);
        
        // Rate limiting para APIs
        $middleware->throttleApi('60,1'); // 60 requests por minuto por IP
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
