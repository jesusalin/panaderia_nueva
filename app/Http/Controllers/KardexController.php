<?php
namespace App\Http\Controllers;

use App\Models\KardexProducto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KardexController extends Controller
{
    public function index(Request $request)
    {
        $query = KardexProducto::with(['producto.categoria', 'usuario'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('id_producto'))
            $query->where('id_producto', $request->id_producto);
        if ($request->filled('tipo'))
            $query->where('tipo', $request->tipo);
        if ($request->filled('fecha_desde'))
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))
            $query->whereDate('created_at', '<=', $request->fecha_hasta);

        $stats = [
            'entradas' => (clone $query)->where('tipo', 'entrada')->count(),
            'salidas'  => (clone $query)->where('tipo', 'salida')->count(),
            'hoy'      => (clone $query)->whereDate('created_at', now())->count(),
            'ajustes'  => (clone $query)->where('motivo', 'ajuste_manual')->count(),
        ];

        $movimientos = $query->paginate(20)->withQueryString();
        $productos   = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('kardex.index', compact('movimientos', 'productos', 'stats'));
    }

    public function rotacion(Request $request)
    {
        $mes = $request->get('mes', Carbon::now()->month);
        $año = $request->get('año', Carbon::now()->year);

        $rotacion = DB::table('venta_detalles')
            ->join('ventas',    'ventas.id',    '=', 'venta_detalles.id_venta')
            ->join('productos', 'productos.id', '=', 'venta_detalles.id_producto')
            ->join('categorias','categorias.id','=', 'productos.id_categoria')
            ->whereYear('ventas.fecha_venta',  $año)
            ->whereMonth('ventas.fecha_venta', $mes)
            ->where('ventas.estado', 'completada')
            ->select(
                'productos.id',
                'productos.nombre',
                'productos.imagen',
                'categorias.nombre as categoria',
                'productos.stock_actual',
                'productos.stock_minimo',
                'productos.precio_venta',
                'productos.costo_produccion',
                DB::raw('SUM(venta_detalles.cantidad) as total_vendido'),
                DB::raw('SUM(venta_detalles.subtotal) as total_ingresos')
            )
            ->groupBy('productos.id','productos.nombre','productos.imagen','categorias.nombre','productos.stock_actual','productos.stock_minimo','productos.precio_venta','productos.costo_produccion')
            ->orderByDesc('total_vendido')
            ->get();

        // Agregar utilidad por producto
        $rotacion = $rotacion->map(function($p) {
            $p->utilidad    = ($p->precio_venta - $p->costo_produccion) * $p->total_vendido;
            $p->margen      = $p->precio_venta > 0
                ? round((($p->precio_venta - $p->costo_produccion) / $p->precio_venta) * 100, 1)
                : 0;
            return $p;
        });

        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                  7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];

        // Resumen agrupado por categoría (panes, pasteles, bebidas, etc.)
        $totalIngresosGeneral = $rotacion->sum('total_ingresos');
        $porCategoria = $rotacion
            ->groupBy('categoria')
            ->map(function ($productosCat, $nombreCategoria) use ($totalIngresosGeneral) {
                $ingresos = $productosCat->sum('total_ingresos');
                return (object) [
                    'categoria'    => $nombreCategoria,
                    'productos'    => $productosCat->count(),
                    'vendido'      => $productosCat->sum('total_vendido'),
                    'ingresos'     => $ingresos,
                    'utilidad'     => $productosCat->sum('utilidad'),
                    'participacion'=> $totalIngresosGeneral > 0 ? round(($ingresos / $totalIngresosGeneral) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('ingresos')
            ->values();

        return view('kardex.rotacion', compact('rotacion', 'mes', 'año', 'meses', 'porCategoria'));
    }
}
