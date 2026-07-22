<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Actualiza silenciosamente la marca de tiempo "ultimo_acceso" del usuario
 * autenticado en cada petición. Con esto, el listado de Usuarios puede
 * mostrar quién está conectado ahora mismo (ver Usuario::estaConectado()).
 *
 * Se limita a una actualización cada minuto por usuario para no generar
 * una consulta UPDATE en cada clic/petición.
 */
class ActualizarUltimoAcceso
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $usuario = auth()->user();
            if (is_null($usuario->ultimo_acceso) || $usuario->ultimo_acceso->diffInSeconds(now()) >= 60) {
                $usuario->timestamps = false; // no tocar updated_at solo por esto
                $usuario->update(['ultimo_acceso' => now()]);
            }
        }

        return $next($request);
    }
}
