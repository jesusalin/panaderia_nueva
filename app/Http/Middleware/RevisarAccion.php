<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea las acciones que el admin le quitó al usuario (ver Usuario::ACCIONES), aunque tenga
 * la pantalla. Por ejemplo, un vendedor con Productos pero sin "Crear, editar y eliminar
 * productos" no puede abrir el formulario de edición ni guardar cambios, aunque escriba la
 * dirección a mano.
 */
class RevisarAccion
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        $ruta = $request->route()?->getName();
        $accion = $ruta ? (Usuario::rutasConAccion()[$ruta] ?? null) : null;

        if ($usuario && $accion && !$usuario->puede($accion)) {
            $mensaje = 'No tienes permiso para: ' . mb_strtolower(Usuario::clavesAccion()[$accion]) . '. Pídeselo al administrador.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $mensaje], 403);
            }
            $anterior = url()->previous();
            $destino = $anterior && $anterior !== $request->fullUrl() ? $anterior : route('dashboard');
            return redirect()->to($destino)->with('error', $mensaje);
        }

        return $next($request);
    }
}
