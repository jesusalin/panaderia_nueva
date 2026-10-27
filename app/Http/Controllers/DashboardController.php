<?php
namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\MateriaPrima;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\MovimientoInventario;
use App\Models\ConteoFisico;
use App\Models\OrdenAutomatica;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dashboard por BLOQUES: cada usuario ve los bloques de las áreas que tiene
 * permitidas (ver Usuario::PERMISOS), así sirve para cualquier rol que arme el admin.
 *   - Accesos rápidos según sus pantallas
 *   - Almacén: hoy en el almacén, PER del último conteo, por recibir, stock crítico
 *   - Producción: lo producido hoy y lo que toca producir
 *   - Ventas: ventas del día/mes, gráfico, más vendidos, productos por agotarse
 *   - Admin: ve todos los bloques
 * El almacenero (usuario de la tesis) ve primero lo del almacén.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $hoy  = Carbon::today();
        $ayer = Carbon::yesterday();

        // ══════════ ALMACÉN ══════════

        // ── Hoy en el almacén: movimientos de insumos del día ──
        $movHoy = MovimientoInventario::whereDate('created_at', $hoy)
            ->select('tipo', DB::raw('count(*) as n'))->groupBy('tipo')->pluck('n', 'tipo');
        $almacenHoy = [
            'entradas'  => (int) ($movHoy['entrada'] ?? 0),
            'salidas'   => (int) ($movHoy['salida'] ?? 0),
            'ajustes'   => (int) ($movHoy['ajuste'] ?? 0),
            'insumos'   => MovimientoInventario::whereDate('created_at', $hoy)->distinct('id_materia')->count('id_materia'),
            'ultimo'    => MovimientoInventario::whereDate('created_at', $hoy)->max('created_at'),
        ];

        // ── PER: Porcentaje de errores de registro (fórmula de la tesis) ──
        // PER = (n.° de insumos con diferencia / n.° de insumos verificados) × 100,
        // sobre cada conteo físico. Se muestran los últimos 6 para ver la tendencia.
        $conteos = ConteoFisico::select(
                'lote',
                DB::raw('min(created_at) as fecha'),
                DB::raw('count(*) as verificados'),
                DB::raw('sum(case when diferencia <> 0 then 1 else 0 end) as con_diferencia')
            )
            ->groupBy('lote')->orderByDesc('fecha')->take(6)->get()
            ->map(function ($c) {
                $c->per = $c->verificados > 0 ? round($c->con_diferencia / $c->verificados * 100, 1) : 0;
                return $c;
            })
            ->reverse()->values();
        $ultimoConteo = $conteos->last();   // null si todavía no hay conteos

        // ── Por recibir: compras pendientes y órdenes automáticas pendientes ──
        $comprasPendientes = Compra::with('proveedor')->where('estado', 'pendiente')
            ->orderBy('fecha_compra')->get();
        $ordenesPendientes = OrdenAutomatica::where('estado', 'pendiente')->count();
        $ordenesSinProveedor = OrdenAutomatica::where('estado', 'pendiente')->whereNull('id_proveedor')->count();

        // ── Stock bajo ──
        $productosStockBajo = Producto::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();
        $materiaStockBaja = MateriaPrima::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        // Consumo diario promedio de cada insumo (salidas de los últimos 7 días):
        // sirve para estimar para cuántos días alcanza lo que queda.
        $consumoDiario = MovimientoInventario::where('tipo', 'salida')
            ->where('created_at', '>=', $hoy->copy()->subDays(7))
            ->select('id_materia', DB::raw('sum(cantidad) / 7 as diario'))
            ->groupBy('id_materia')->pluck('diario', 'id_materia');

        // Los ítems más urgentes de reponer (insumos primero, que es lo que maneja el almacén)
        $criticosMateria = MateriaPrima::with('unidad')->where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->get()
            ->map(fn($m) => (object) [
                'tipo' => 'materia', 'id' => $m->id, 'nombre' => $m->nombre,
                'stock' => (float) $m->stock_actual, 'minimo' => (float) $m->stock_minimo,
                'unidad' => $m->unidad->abreviatura ?? '',
                'dias' => ($consumoDiario[$m->id] ?? 0) > 0 ? $m->stock_actual / $consumoDiario[$m->id] : null,
            ]);
        $criticosProducto = Producto::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->get()
            ->map(fn($p) => (object) [
                'tipo' => 'producto', 'id' => $p->id, 'nombre' => $p->nombre,
                'stock' => (float) $p->stock_actual, 'minimo' => (float) $p->stock_minimo,
                'unidad' => 'uds', 'dias' => null,
            ]);
        $nivel = fn($i) => $i->minimo > 0 ? $i->stock / $i->minimo : 0;
        $stockCritico = $criticosMateria->sortBy($nivel)
            ->concat($criticosProducto->sortBy($nivel))
            ->take(8)->values();

        // ══════════ PRODUCCIÓN ══════════
        // Lo producido hoy (por producto) y los productos en su mínimo que toca hornear.
        $producidoHoy = DB::table('producciones')
            ->join('productos', 'productos.id', '=', 'producciones.id_producto')
            ->whereDate('producciones.fecha', $hoy)
            ->select('productos.nombre', DB::raw('SUM(producciones.cantidad) as cantidad'))
            ->groupBy('productos.id', 'productos.nombre')->orderByDesc('cantidad')->get();
        $porProducir = Producto::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderByRaw('stock_actual * 1.0 / CASE WHEN stock_minimo = 0 THEN 1 ELSE stock_minimo END')
            ->take(6)->get();

        // ══════════ VENTAS (solo quien tiene el módulo) ══════════
        // Productos que se están acabando (hasta 1.5 veces su mínimo): aviso para el mostrador.
        $porAgotarse = Producto::where('estado', 'activo')
            ->whereRaw('stock_actual <= stock_minimo * 1.5')
            ->orderByRaw('stock_actual * 1.0 / CASE WHEN stock_minimo = 0 THEN 1 ELSE stock_minimo END')
            ->take(6)->get();

        $ventasHoy = Venta::whereDate('fecha_venta', $hoy)->where('estado', 'completada')->sum('total');
        $ventasAyer = Venta::whereDate('fecha_venta', $ayer)->where('estado', 'completada')->sum('total');
        $variacionVentas = $ventasAyer > 0
            ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 1)
            : null;

        $ventasMes = Venta::whereYear('fecha_venta', $hoy->year)->whereMonth('fecha_venta', $hoy->month)
            ->where('estado', 'completada')->sum('total');
        $comprasMes = Compra::whereYear('fecha_compra', $hoy->year)->whereMonth('fecha_compra', $hoy->month)
            ->where('estado', 'recibida')->sum('total');
        $ventasMenosCompras = $ventasMes - $comprasMes;

        $ultimasVentas = Venta::with(['usuario', 'cliente'])->orderBy('created_at', 'desc')->take(5)->get();

        $topProductos = DB::table('venta_detalles')
            ->join('ventas',   'ventas.id',   '=', 'venta_detalles.id_venta')
            ->join('productos','productos.id','=', 'venta_detalles.id_producto')
            ->whereYear('ventas.fecha_venta',  $hoy->year)
            ->whereMonth('ventas.fecha_venta', $hoy->month)
            ->where('ventas.estado', 'completada')
            ->select('productos.nombre', DB::raw('SUM(venta_detalles.cantidad) as total_vendido'))
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_vendido')->take(5)->get();

        // Ventas de los últimos 7 días, con los días sin ventas en cero
        $porDia = Venta::where('estado', 'completada')
            ->where('fecha_venta', '>=', $hoy->copy()->subDays(6))
            ->select(DB::raw('DATE(fecha_venta) as fecha'), DB::raw('SUM(total) as total'))
            ->groupBy('fecha')->pluck('total', 'fecha');
        $ventasSemana = collect(range(6, 0))->map(function ($atras) use ($hoy, $porDia) {
            $dia = $hoy->copy()->subDays($atras);
            return [
                'etiqueta' => $dia->locale('es')->isoFormat('ddd D'),
                'total'    => round((float) ($porDia[$dia->toDateString()] ?? 0), 2),
            ];
        });

        return view('dashboard.index', compact(
            'almacenHoy', 'conteos', 'ultimoConteo',
            'comprasPendientes', 'ordenesPendientes', 'ordenesSinProveedor',
            'productosStockBajo', 'materiaStockBaja', 'stockCritico',
            'producidoHoy', 'porProducir', 'porAgotarse',
            'ventasHoy', 'variacionVentas', 'ventasMes', 'comprasMes', 'ventasMenosCompras',
            'ultimasVentas', 'topProductos', 'ventasSemana'
        ));
    }
}
