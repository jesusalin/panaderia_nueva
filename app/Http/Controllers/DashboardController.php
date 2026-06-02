<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\MateriaPrima;
use App\Models\Venta;
use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // Ventas del día
        $ventasHoy = Venta::whereDate('fecha_venta', $hoy)
            ->where('estado', 'completada')->sum('total');

        // Total ventas del mes
        $ventasMes = Venta::whereYear('fecha_venta', $hoy->year)
            ->whereMonth('fecha_venta', $hoy->month)
            ->where('estado', 'completada')->sum('total');

        // Compras del mes
        $comprasMes = Compra::whereYear('fecha_compra', $hoy->year)
            ->whereMonth('fecha_compra', $hoy->month)
            ->where('estado', 'recibida')->sum('total');

        // Alertas de stock bajo
        $productosStockBajo = Producto::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        $materiaStockBaja = MateriaPrima::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        // Últimas 5 ventas
        $ultimasVentas = Venta::with(['usuario', 'cliente'])
            ->orderBy('created_at', 'desc')->take(5)->get();

        // Productos más vendidos (mes actual)
        $topProductos = DB::table('venta_detalles')
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.id_venta')
            ->join('productos', 'productos.id', '=', 'venta_detalles.id_producto')
            ->whereYear('ventas.fecha_venta', $hoy->year)
            ->whereMonth('ventas.fecha_venta', $hoy->month)
            ->where('ventas.estado', 'completada')
            ->select('productos.nombre', DB::raw('SUM(venta_detalles.cantidad) as total_vendido'))
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_vendido')
            ->take(5)->get();

        // Ventas últimos 7 días para gráfico
        $ventasSemana = Venta::where('estado', 'completada')
            ->where('fecha_venta', '>=', Carbon::now()->subDays(6))
            ->select(DB::raw('DATE(fecha_venta) as fecha'), DB::raw('SUM(total) as total'))
            ->groupBy('fecha')->orderBy('fecha')->get();

        return view('dashboard.index', compact(
            'ventasHoy', 'ventasMes', 'comprasMes',
            'productosStockBajo', 'materiaStockBaja',
            'ultimasVentas', 'topProductos', 'ventasSemana'
        ));
    }
}
