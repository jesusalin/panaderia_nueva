<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Orden del menú lateral. Solo el ADMIN lo puede cambiar (botón "Ordenar menú"
 * del sidebar, arrastrando y soltando) y el orden que guarda se aplica a TODOS
 * los usuarios. Se guarda en la tabla configuraciones (clave "orden_menu") y se
 * aplica en layouts/app.blade.php.
 *
 * Solo se guardan identificadores (data-menu-id del sidebar), nunca rutas ni
 * HTML: el orden no da acceso a nada, cada usuario sigue viendo solo los
 * módulos que sus permisos le permiten, en el orden que definió el admin.
 */
class MenuController extends Controller
{
    public const CLAVE = 'orden_menu';
    private const PATRON_ID = '/^[a-z0-9\-]{1,50}$/';

    public function guardar(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Solo el administrador puede ordenar el menú.');

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

        Configuracion::guardar(self::CLAVE, [
            'secciones' => array_values(array_unique($datos['secciones'])),
            'items'     => $items,
        ]);

        return response()->json(['ok' => true, 'mensaje' => 'Orden del menú guardado para todos los usuarios']);
    }

    public function restablecer(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Solo el administrador puede ordenar el menú.');

        Configuracion::borrar(self::CLAVE);

        return response()->json(['ok' => true, 'mensaje' => 'Se restableció el orden original']);
    }
}
