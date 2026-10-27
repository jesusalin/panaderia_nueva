<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulo
{
    /**
     * Uso en rutas:
     *   ->middleware('modulo:inventario.conteo')                 una pantalla
     *   ->middleware('modulo:produccion.produccion,produccion.recetas')  cualquiera de varias
     *   ->middleware('modulo:ventas')                            módulo sin pantallas
     * El admin siempre pasa. Ver Usuario::hasPermiso.
     */
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();

        $puede = $usuario && collect($permisos)->contains(fn ($p) => $usuario->hasPermiso($p));

        if (!$puede) {
            abort(403, 'No tienes permiso para acceder a esta pantalla.');
        }

        return $next($request);
    }
}
