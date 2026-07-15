<?php
namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\MateriaPrima;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\KardexProducto;
use App\Models\MovimientoInventario;
use App\Models\TiempoOperacion;
use App\Models\TiempoBaseline;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $ayer = Carbon::yesterday();

        // ── Ventas ──────────────────────────────────────────
        $ventasHoy = Venta::whereDate('fecha_venta', $hoy)
            ->where('estado', 'completada')->sum('total');

        $ventasAyer = Venta::whereDate('fecha_venta', $ayer)
            ->where('estado', 'completada')->sum('total');

        $variacionVentas = $ventasAyer > 0
            ? round((($ventasHoy - $ventasAyer) / $ventasAyer) * 100, 1)
            : null;

        $ventasMes = Venta::whereYear('fecha_venta', $hoy->year)
            ->whereMonth('fecha_venta', $hoy->month)
            ->where('estado', 'completada')->sum('total');

        // ── Compras ─────────────────────────────────────────
        $comprasMes = Compra::whereYear('fecha_compra', $hoy->year)
            ->whereMonth('fecha_compra', $hoy->month)
            ->where('estado', 'recibida')->sum('total');

        // ── Ganancia bruta del mes ───────────────────────────
        $gananciaMes = $ventasMes - $comprasMes;

        // ── Alertas de stock ────────────────────────────────
        $productosStockBajo = Producto::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        $materiaStockBaja = MateriaPrima::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        // ── Exactitud del inventario (OE2 de tesis) ──────────
        // % de insumos cuyo stock no necesitó corrección este mes
        $totalMateriasActivas = MateriaPrima::where('estado', 'activo')->count();
        $materiasConAjusteMes = MovimientoInventario::where('tipo', 'ajuste')
            ->whereYear('created_at', $hoy->year)
            ->whereMonth('created_at', $hoy->month)
            ->distinct('id_materia')->count('id_materia');

        $exactitudInventario = $totalMateriasActivas > 0
            ? round((($totalMateriasActivas - $materiasConAjusteMes) / $totalMateriasActivas) * 100, 1)
            : 100;

        // ── Registros procesados hoy (KPI tesis) ─────────────
        $registrosHoy = KardexProducto::whereDate('created_at', $hoy)->count()
            + MovimientoInventario::whereDate('created_at', $hoy)->count()
            + Venta::whereDate('created_at', $hoy)->count()
            + Compra::whereDate('created_at', $hoy)->count();

        // ── Reducción de tiempos muertos (OE3 de tesis) ──────
        // Promedio de reducción % entre las 3 operaciones cronometradas
        $promediosTiempo = TiempoOperacion::select('tipo_operacion', DB::raw('AVG(duracion_ms) as promedio_ms'))
            ->groupBy('tipo_operacion')->get()->keyBy('tipo_operacion');
        $baselinesTiempo = TiempoBaseline::all()->keyBy('tipo_operacion');

        $reducciones = [];
        foreach (TiempoOperacion::TIPOS as $tipo => $etiqueta) {
            $finalSeg   = isset($promediosTiempo[$tipo]) ? $promediosTiempo[$tipo]->promedio_ms / 1000 : null;
            $inicialSeg = isset($baselinesTiempo[$tipo]) ? (float) $baselinesTiempo[$tipo]->segundos_manual : 0;
            if ($finalSeg && $finalSeg > 0 && $inicialSeg > 0) {
                $reducciones[] = (($inicialSeg - $finalSeg) / $finalSeg) * 100;
            }
        }
        $reduccionTiemposMuertos = count($reducciones) > 0 ? round(array_sum($reducciones) / count($reducciones), 1) : null;

        // ── Últimas 5 ventas ────────────────────────────────
        $ultimasVentas = Venta::with(['usuario', 'cliente'])
            ->orderBy('created_at', 'desc')->take(5)->get();

        // ── Top 5 productos más vendidos (mes) ───────────────
        $topProductos = DB::table('venta_detalles')
            ->join('ventas',   'ventas.id',   '=', 'venta_detalles.id_venta')
            ->join('productos','productos.id','=', 'venta_detalles.id_producto')
            ->whereYear('ventas.fecha_venta',  $hoy->year)
            ->whereMonth('ventas.fecha_venta', $hoy->month)
            ->where('ventas.estado', 'completada')
            ->select('productos.nombre', DB::raw('SUM(venta_detalles.cantidad) as total_vendido'))
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_vendido')
            ->take(5)->get();

        // ── Ventas últimos 7 días (gráfico) ──────────────────
        $ventasSemana = Venta::where('estado', 'completada')
            ->where('fecha_venta', '>=', Carbon::now()->subDays(6))
            ->select(DB::raw('DATE(fecha_venta) as fecha'), DB::raw('SUM(total) as total'))
            ->groupBy('fecha')->orderBy('fecha')->get();

        // ── Rotación de stock (top 5 este mes) ───────────────
        $rotacionStock = DB::table('venta_detalles')
            ->join('ventas',   'ventas.id',   '=', 'venta_detalles.id_venta')
            ->join('productos','productos.id','=', 'venta_detalles.id_producto')
            ->whereYear('ventas.fecha_venta',  $hoy->year)
            ->whereMonth('ventas.fecha_venta', $hoy->month)
            ->where('ventas.estado', 'completada')
            ->select(
                'productos.nombre',
                'productos.stock_actual',
                DB::raw('SUM(venta_detalles.cantidad) as total_vendido')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.stock_actual')
            ->orderByDesc('total_vendido')
            ->take(5)->get();

        return view('dashboard.index', compact(
            'ventasHoy', 'ventasAyer', 'variacionVentas', 'ventasMes', 'comprasMes', 'gananciaMes',
            'productosStockBajo', 'materiaStockBaja',
            'exactitudInventario', 'registrosHoy', 'reduccionTiemposMuertos',
            'ultimasVentas', 'topProductos', 'ventasSemana', 'rotacionStock'
        ));
    }
}
