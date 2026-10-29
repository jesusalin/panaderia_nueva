<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'modulo' => \App\Http\Middleware\CheckModulo::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\ActualizarUltimoAcceso::class,
            \App\Http\Middleware\RegistrarActividad::class,   // historial de actividad
            \App\Http\Middleware\ExigirCambioPassword::class, // contraseña temporal → elegir una propia
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
