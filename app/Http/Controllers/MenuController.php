<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Guarda el orden del menú lateral que cada usuario arma arrastrando y
 * soltando (botón "Ordenar menú" del sidebar). El orden es personal: se
 * guarda en usuarios.orden_menu y se aplica en layouts/app.blade.php.
 *
 * Solo se guardan identificadores (data-menu-id del sidebar), nunca rutas ni
 * HTML, así que un usuario no puede "darse" acceso a un módulo cambiando el
 * orden: el menú sigue mostrando solo lo que sus permisos permiten.
 */
class MenuController extends Controller
{
    private const PATRON_ID = '/^[a-z0-9\-]{1,50}$/';

    public function guardar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'secciones'   => ['present', 'array', 'max:50'],
            'secciones.*' => ['string', 'regex:' . self::PATRON_ID],
            'items'       => ['present', 'array', 'max:50'],
            'items.*'     => ['array', 'max:50'],
            'items.*.*'   => ['string', 'regex:' . self::PATRON_ID],
        ]);

        // Las claves de "items" (id de cada sección) también deben ser ids válidos.
        $items = [];
        foreach ($datos['items'] as $seccion => $ids) {
            if (is_string($seccion) && preg_match(self::PATRON_ID, $seccion)) {
                $items[$seccion] = array_values(array_unique($ids));
            }
        }

        $request->user()->update([
            'orden_menu' => [
                'secciones' => array_values(array_unique($datos['secciones'])),
                'items'     => $items,
            ],
        ]);

        return response()->json(['ok' => true, 'mensaje' => 'Orden del menú guardado']);
    }

    public function restablecer(Request $request): JsonResponse
    {
        $request->user()->update(['orden_menu' => null]);

        return response()->json(['ok' => true, 'mensaje' => 'Se restableció el orden original']);
    }
}
