<?php

namespace App\Http\Controllers;

use App\Models\ActividadUsuario;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Support\Cantidad;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Lista para las sugerencias de los buscadores: al hacer clic en "Buscar…" se muestra
 * todo, y mientras se escribe la lista se va achicando (el filtrado lo hace el navegador).
 * Cada fuente pide el mismo permiso que la pantalla donde está el buscador.
 *
 * Cada elemento: t = texto que se busca, s = dato de apoyo, i = ícono, k = otras palabras clave.
 */
class SugerenciasController extends Controller
{
    private const LIMITE = 500;

    /** Fuente => permisos (basta con tener uno). */
    private const PERMISOS = [
        'productos'   => ['catalogo.productos', 'produccion.recetas', 'produccion.produccion', 'reportes.stock'],
        'insumos'     => ['inventario.materia_prima', 'inventario.movimientos', 'reportes.stock'],
        'stock'       => ['reportes.stock'],
        'categorias'  => ['catalogo.categorias'],
        'usuarios'    => ['usuarios'],
        'clientes'    => ['clientes'],
        'proveedores' => ['compras.proveedores'],
        'actividad'   => ['usuarios'],
    ];

    public function __invoke(Request $request, string $fuente)
    {
        abort_unless(isset(self::PERMISOS[$fuente]), 404);
        $u = $request->user();
        abort_unless(collect(self::PERMISOS[$fuente])->contains(fn ($c) => $u->hasPermiso($c)), 403);

        return response()->json($this->construir($fuente)->values())
            ->header('Cache-Control', 'private, max-age=30');
    }

    private function construir(string $fuente): Collection
    {
        return match ($fuente) {
            'productos' => Producto::with('categoria')->orderBy('nombre')->limit(self::LIMITE)->get()->map(fn ($p) => [
                't' => $p->nombre,
                's' => ($p->categoria->nombre ?? 'Sin categoría') . ' · stock ' . $p->stock_actual . ($p->estado !== 'activo' ? ' · inactivo' : ''),
                'i' => $p->categoria->icono_final ?? 'fa-bread-slice',
            ]),
            'insumos' => MateriaPrima::with('unidad')->orderBy('nombre')->limit(self::LIMITE)->get()->map(fn ($m) => [
                't' => $m->nombre,
                's' => 'Insumo · stock ' . Cantidad::conUnidad($m->stock_actual, $m->unidad->abreviatura ?? '') . ($m->estado !== 'activo' ? ' · inactivo' : ''),
                'i' => 'fa-wheat-awn',
            ]),
            'stock' => $this->construir('insumos')->merge($this->construir('productos'))->sortBy('t', SORT_NATURAL | SORT_FLAG_CASE),
            'categorias' => Categoria::withCount('productos')->orderBy('nombre')->get()->map(fn ($c) => [
                't' => $c->nombre, 's' => $c->productos_count . ' producto(s)', 'i' => $c->icono_final,
            ]),
            'usuarios' => Usuario::orderBy('nombre')->get()->map(fn ($x) => [
                't' => $x->nombre, 's' => trim(($x->apodo ? $x->apodo . ' · ' : '') . 'usuario: ' . $x->usuario), 'i' => 'fa-user',
                'k' => $x->usuario . ' ' . $x->email . ' ' . $x->apodo,
            ]),
            'clientes' => Cliente::orderBy('nombre')->limit(self::LIMITE)->get()->map(fn ($c) => [
                't' => $c->nombre, 's' => (string) ($c->distrito ?: $c->telefono), 'i' => 'fa-user-friends',
                'k' => trim($c->ruc . ' ' . $c->dni . ' ' . $c->distrito),
            ]),
            'proveedores' => Proveedor::orderBy('nombre')->get()->map(fn ($p) => [
                't' => $p->nombre, 's' => trim(($p->ruc ? 'RUC ' . $p->ruc : '') . ($p->contacto ? ' · ' . $p->contacto : ''), ' ·'), 'i' => 'fa-truck',
                'k' => trim($p->ruc . ' ' . $p->contacto),
            ]),
            'actividad' => ActividadUsuario::select('descripcion')->whereNotIn('accion', ['inicio_sesion', 'cierre_sesion'])
                ->groupBy('descripcion')->orderByRaw('MAX(created_at) DESC')->limit(300)->pluck('descripcion')
                ->map(fn ($d) => ['t' => $d, 's' => '', 'i' => 'fa-history']),
        };
    }
}
