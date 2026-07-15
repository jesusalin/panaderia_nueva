<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Uso en rutas: ->middleware('role:admin,almacenero')
     * Si el usuario no tiene ninguno de los roles indicados, se le
     * devuelve un 403 (acceso denegado) en vez de dejarlo entrar.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->hasRole($roles)) {
            abort(403, 'No tienes permiso para acceder a este módulo.');
        }

        return $next($request);
    }
}
