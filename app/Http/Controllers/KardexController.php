<?php
namespace App\Http\Controllers;

use App\Models\KardexProducto;
use App\Models\Producto;
use Illuminate\Http\Request;

/** Movimientos de productos terminados: producción, ventas, ajustes y devoluciones. */
class KardexController extends Controller
{
    public const MOTIVOS = ['produccion' => 'Producción', 'venta' => 'Venta', 'ajuste_manual' => 'Ajuste / merma', 'devolucion' => 'Devolución'];

    public function index(Request $request)
    {
        $request->validate([
            'id_producto' => 'nullable|integer', 'buscar' => 'nullable|string|max:100',
            'tipo' => 'nullable|in:entrada,salida', 'motivo' => 'nullable|in:' . implode(',', array_keys(self::MOTIVOS)),
            'periodo' => 'nullable|in:' . implode(',', array_keys(MovimientosController::PERIODOS)),
            'desde' => 'nullable|date', 'hasta' => 'nullable|date',
            'fecha_desde' => 'nullable|date', 'fecha_hasta' => 'nullable|date',
        ]);
        // Compatibilidad con los enlaces antiguos (fecha_desde / fecha_hasta)
        $desde = $request->input('desde', $request->input('fecha_desde'));
        $hasta = $request->input('hasta', $request->input('fecha_hasta'));
        $periodo = $request->input('periodo', ($desde || $hasta) ? null : 'todo');

        $query = KardexProducto::query()
            ->when($request->filled('id_producto'), fn ($q) => $q->where('kardex_productos.id_producto', $request->id_producto))
            ->when($request->filled('buscar'), fn ($q) => $q->whereHas('producto', fn ($p) => $p->where('nombre', 'like', '%' . $request->buscar . '%')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('kardex_productos.tipo', $request->tipo))
            ->when($request->filled('motivo'), fn ($q) => $q->where('kardex_productos.motivo', $request->motivo))
            ->when($periodo === 'hoy', fn ($q) => $q->where('kardex_productos.created_at', '>=', now()->startOfDay()))
            ->when(in_array($periodo, ['7', '30'], true), fn ($q) => $q->where('kardex_productos.created_at', '>=', now()->subDays((int) $periodo - 1)->startOfDay()))
            ->when(!$periodo && $desde, fn ($q) => $q->where('kardex_productos.created_at', '>=', \Carbon\Carbon::parse($desde)->startOfDay()))
            ->when(!$periodo && $hasta, fn ($q) => $q->where('kardex_productos.created_at', '<=', \Carbon\Carbon::parse($hasta)->endOfDay()));

        $resumen = (clone $query)->join('productos', 'productos.id', '=', 'kardex_productos.id_producto')
            ->selectRaw('kardex_productos.tipo as tipo, COUNT(*) as n, SUM(kardex_productos.cantidad) as unidades, SUM(kardex_productos.cantidad * productos.precio_venta) as valor')
            ->groupBy('kardex_productos.tipo')->get()->keyBy('tipo');
        $porMotivo = (clone $query)->selectRaw('motivo, COUNT(*) as n')->groupBy('motivo')->pluck('n', 'motivo');

        $movimientos = $query->with(['producto.categoria', 'usuario'])
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(30)->withQueryString();
        $producto = $request->filled('id_producto') ? Producto::find($request->id_producto) : null;

        return view('kardex.index', compact('movimientos', 'resumen', 'porMotivo', 'periodo', 'producto', 'desde', 'hasta'));
    }
}
