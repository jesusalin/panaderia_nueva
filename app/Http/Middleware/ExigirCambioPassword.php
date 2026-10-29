<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el admin le dio una contraseña temporal, el usuario solo puede entrar a
 * "Mi perfil" hasta que elija su propia contraseña.
 */
class ExigirCambioPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();
        if ($u && $u->debe_cambiar_password && !$request->routeIs('perfil.*', 'logout', 'login')) {
            return redirect()->route('perfil.edit')
                ->with('warning', 'Estás usando una contraseña temporal. Elige tu propia contraseña para continuar.');
        }
        return $next($request);
    }
}
