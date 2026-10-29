<?php

namespace App\Http\Middleware;

use App\Models\ActividadUsuario;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anota en el historial de actividad cada acción que CAMBIA algo (registrar,
 * editar, eliminar, recibir, anular…), con una frase legible para el admin.
 *
 * Solo se registra si la acción salió bien: si hubo error de validación o el
 * controlador devolvió un mensaje de error, no se anota.
 * Los inicios y cierres de sesión los registra el AuthController.
 */
class RegistrarActividad
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) || !auth()->check()) {
            return $response;
        }
        $ruta = $request->route()?->getName();
        if (!$ruta || in_array($ruta, ['logout', 'login'], true)) {
            return $response;
        }
        // ¿Salió bien? (se mira solo lo que se "flasheó" en ESTA petición, no errores de antes)
        $nuevos = $request->hasSession() ? (array) $request->session()->get('_flash.new', []) : [];
        if ($response->getStatusCode() >= 400 || in_array('errors', $nuevos, true) || in_array('error', $nuevos, true)) {
            return $response;
        }

        [$accion, $modulo, $descripcion] = $this->describir($ruta, $request);
        ActividadUsuario::registrar($accion, $descripcion, $modulo);

        return $response;
    }

    /** @return array{0:string,1:?string,2:string} [acción, módulo, descripción] */
    private function describir(string $ruta, Request $r): array
    {
        $m   = collect($r->route()->parameters())->first(fn ($p) => $p instanceof Model);
        $n   = fn () => $m ? '"' . ($m->nombre ?? $m->numero_venta ?? ('#' . $m->getKey())) . '"' : '';
        $id  = fn () => $m ? '#' . $m->getKey() : '';
        $est = fn () => $m && isset($m->estado) ? ($m->estado === 'activo' ? 'activó' : 'desactivó') : 'cambió el estado de';
        $in  = fn (string $campo) => (string) $r->input($campo);
        $mio = fn (string $clase) => $clase::where('id_usuario', auth()->id())->latest('id')->first();

        $mapa = [
            // Catálogo
            'categorias.store'        => ['crear',    'Catálogo', fn () => 'Creó la categoría "' . $in('nombre') . '"'],
            'categorias.update'       => ['editar',   'Catálogo', fn () => 'Editó la categoría ' . $n()],
            'categorias.destroy'      => ['eliminar', 'Catálogo', fn () => 'Eliminó la categoría ' . $n()],
            'categorias.toggle-estado'=> ['editar',   'Catálogo', fn () => ucfirst($est()) . ' la categoría ' . $n()],
            'productos.store'         => ['crear',    'Catálogo', fn () => 'Creó el producto "' . $in('nombre') . '"'],
            'productos.update'        => ['editar',   'Catálogo', fn () => 'Editó el producto ' . $n()],
            'productos.destroy'       => ['eliminar', 'Catálogo', fn () => 'Eliminó el producto ' . $n()],
            'productos.toggle-estado' => ['editar',   'Catálogo', fn () => ucfirst($est()) . ' el producto ' . $n()],
            'productos.ajuste.store'  => ['editar',   'Catálogo', fn () => 'Ajustó el stock de ' . $n() . ' (' . ($in('tipo_ajuste') === 'merma' ? 'merma de ' . $in('cantidad') . ' uds' : 'conteo: ' . $in('stock_real') . ' uds') . ')'],
            // Inventario
            'materia-prima.store'         => ['crear',    'Inventario', fn () => 'Creó el insumo "' . $in('nombre') . '"'],
            'materia-prima.update'        => ['editar',   'Inventario', fn () => 'Editó el insumo ' . $n()],
            'materia-prima.destroy'       => ['eliminar', 'Inventario', fn () => 'Eliminó el insumo ' . $n()],
            'materia-prima.toggle-estado' => ['editar',   'Inventario', fn () => ucfirst($est()) . ' el insumo ' . $n()],
            'materia-prima.ajuste.store'  => ['editar',   'Inventario', fn () => 'Ajustó el stock de ' . $n() . ' a ' . $in('stock_real')],
            'conteo-fisico.store'         => ['crear',    'Inventario', fn () => 'Registró un conteo físico de ' . collect($r->input('conteo', []))->filter(fn ($i) => ($i['stock_fisico'] ?? '') !== '' && ($i['stock_fisico'] ?? null) !== null)->count() . ' insumo(s)'],
            // Producción
            'produccion.store'          => ['crear',    'Producción', fn () => 'Registró producción de ' . $in('cantidad') . ' uds de "' . (Producto::find($r->input('id_producto'))->nombre ?? 'producto') . '"'],
            'produccion.destroy'        => ['eliminar', 'Producción', fn () => 'Eliminó la producción ' . $id()],
            'produccion.guardar-receta' => ['editar',   'Producción', fn () => 'Guardó la receta de "' . (Producto::find($r->input('id_producto'))->nombre ?? 'producto') . '"'],
            // Compras
            'proveedores.store'         => ['crear',    'Compras', fn () => 'Creó el proveedor "' . $in('nombre') . '"'],
            'proveedores.update'        => ['editar',   'Compras', fn () => 'Editó el proveedor ' . $n()],
            'proveedores.destroy'       => ['eliminar', 'Compras', fn () => 'Eliminó el proveedor ' . $n()],
            'proveedores.toggle-estado' => ['editar',   'Compras', fn () => ucfirst($est()) . ' el proveedor ' . $n()],
            'compras.store'             => ['crear',    'Compras', fn () => 'Registró una compra a "' . (Proveedor::find($r->input('id_proveedor'))->nombre ?? 'proveedor') . '"' . (($c = $mio(Compra::class)) ? " (compra #{$c->id})" : '')],
            'compras.recibir'           => ['editar',   'Compras', fn () => 'Recibió la compra ' . $id() . ' (entró al stock)'],
            'compras.anular'            => ['eliminar', 'Compras', fn () => 'Anuló la compra ' . $id()],
            'ordenes-automaticas.generar'   => ['crear', 'Compras', fn () => 'Generó órdenes automáticas de reposición'],
            'ordenes-automaticas.convertir' => ['crear', 'Compras', fn () => 'Convirtió la orden automática ' . $id() . ' en compra'],
            'ordenes-automaticas.descartar' => ['eliminar', 'Compras', fn () => 'Descartó la orden automática ' . $id()],
            // Ventas y clientes
            'ventas.store'   => ['crear',    'Ventas', fn () => 'Registró la venta ' . (($v = $mio(Venta::class)) ? "{$v->numero_venta} por S/ " . number_format($v->total, 2) : '')],
            'ventas.anular'  => ['eliminar', 'Ventas', fn () => 'Anuló la venta ' . $n()],
            'ventas.destroy' => ['eliminar', 'Ventas', fn () => 'Eliminó la venta ' . $n()],
            'clientes.store'   => ['crear',    'Clientes', fn () => 'Registró al cliente "' . $in('nombre') . '"'],
            'clientes.update'  => ['editar',   'Clientes', fn () => 'Editó al cliente ' . $n()],
            'clientes.destroy' => ['eliminar', 'Clientes', fn () => 'Eliminó al cliente ' . $n()],
            // Administración
            'usuarios.store'         => ['crear',    'Usuarios', fn () => 'Creó al usuario "' . $in('usuario') . '"'],
            'usuarios.update'        => ['editar',   'Usuarios', fn () => 'Editó al usuario ' . $n() . ' (datos o permisos)'],
            'usuarios.destroy'       => ['eliminar', 'Usuarios', fn () => 'Eliminó al usuario ' . $n()],
            'usuarios.toggle-estado' => ['editar',   'Usuarios', fn () => ucfirst($est()) . ' al usuario ' . $n()],
            'usuarios.clave-temporal'=> ['editar',   'Usuarios', fn () => 'Le dio una contraseña temporal a ' . $n()],
            'perfil.update'          => ['editar',   'Perfil',   fn () => 'Actualizó sus datos de perfil'],
            'perfil.password'        => ['editar',   'Perfil',   fn () => 'Cambió su contraseña'],
            'backups.store'    => ['crear',    'Respaldos', fn () => 'Generó un respaldo de la base de datos'],
            'backups.destroy'  => ['eliminar', 'Respaldos', fn () => 'Eliminó el respaldo ' . $r->route('archivo')],
            'backups.restore'  => ['otra',     'Respaldos', fn () => 'Restauró la base de datos desde ' . $r->route('archivo')],
            'menu.orden.guardar'     => ['editar', 'Sistema', fn () => 'Cambió el orden del menú'],
            'menu.orden.restablecer' => ['editar', 'Sistema', fn () => 'Restableció el orden original del menú'],
        ];

        if (isset($mapa[$ruta])) {
            [$accion, $modulo, $texto] = $mapa[$ruta];
            return [$accion, $modulo, trim($texto())];
        }

        // Cualquier acción nueva que aún no tenga frase propia igual queda registrada
        $verbo = str_ends_with($ruta, '.store') ? 'crear' : (str_ends_with($ruta, '.destroy') ? 'eliminar' : 'editar');
        return [$verbo, null, 'Acción "' . $ruta . '"' . ($m ? ' sobre ' . $n() : '')];
    }
}
