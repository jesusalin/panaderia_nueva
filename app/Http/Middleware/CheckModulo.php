<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulo
{
    /**
     * Uso en rutas: ->middleware('modulo:inventario')
     * El admin siempre pasa. Un usuario normal necesita tener el módulo
     * asignado en permisos_usuario (ver Usuario::hasModulo).
     */
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->hasModulo($modulo)) {
            abort(403, 'No tienes permiso para acceder a este módulo.');
        }

        return $next($request);
    }
}
