<?php
namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\MateriaPrima;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\KardexProducto;
use App\Models\MovimientoInventario;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // ── Ventas ──────────────────────────────────────────
        $ventasHoy = Venta::whereDate('fecha_venta', $hoy)
            ->where('estado', 'completada')->sum('total');

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

        // ── Exactitud del inventario ─────────────────────────
        // % de productos con stock > 0 vs total activos
        $totalProductos     = Producto::where('estado', 'activo')->count();
        $productosConStock  = Producto::where('estado', 'activo')->where('stock_actual', '>', 0)->count();
        $exactitudInventario = $totalProductos > 0
            ? round(($productosConStock / $totalProductos) * 100, 1)
            : 0;

        // ── Registros procesados hoy (KPI tesis) ─────────────
        $registrosHoy = KardexProducto::whereDate('created_at', $hoy)->count()
            + MovimientoInventario::whereDate('created_at', $hoy)->count()
            + Venta::whereDate('created_at', $hoy)->count()
            + Compra::whereDate('created_at', $hoy)->count();

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
            'ventasHoy', 'ventasMes', 'comprasMes', 'gananciaMes',
            'productosStockBajo', 'materiaStockBaja',
            'exactitudInventario', 'registrosHoy',
            'ultimasVentas', 'topProductos', 'ventasSemana', 'rotacionStock'
        ));
    }
}
