<?php
namespace App\Http\Controllers;
use App\Models\MovimientoInventario;
use App\Models\MateriaPrima;
use Illuminate\Http\Request;

/** Movimientos de materia prima: entradas, salidas y ajustes de insumos. */
class MovimientosController extends Controller
{
    public const MOTIVOS = ['compra' => 'Compra', 'produccion' => 'Producción', 'merma' => 'Merma', 'ajuste_manual' => 'Ajuste / conteo', 'devolucion' => 'Devolución'];
    public const PERIODOS = ['hoy' => 'Hoy', '7' => '7 días', '30' => '30 días', 'todo' => 'Todo'];

    public function index(Request $request) {
        $request->validate([
            'id_materia' => 'nullable|integer', 'buscar' => 'nullable|string|max:100',
            'tipo' => 'nullable|in:entrada,salida,ajuste', 'motivo' => 'nullable|in:' . implode(',', array_keys(self::MOTIVOS)),
            'periodo' => 'nullable|in:' . implode(',', array_keys(self::PERIODOS)), 'desde' => 'nullable|date', 'hasta' => 'nullable|date',
        ]);
        $periodo = $request->input('periodo', $request->hasAny(['desde', 'hasta']) ? null : 'todo');

        $query = MovimientoInventario::query()
            ->when($request->filled('id_materia'), fn ($q) => $q->where('movimientos_inventario.id_materia', $request->id_materia))
            ->when($request->filled('buscar'), fn ($q) => $q->whereHas('materia', fn ($m) => $m->where('nombre', 'like', '%' . $request->buscar . '%')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('movimientos_inventario.tipo', $request->tipo))
            ->when($request->filled('motivo'), fn ($q) => $q->where('movimientos_inventario.motivo', $request->motivo))
            ->when($periodo === 'hoy', fn ($q) => $q->where('movimientos_inventario.created_at', '>=', now()->startOfDay()))
            ->when(in_array($periodo, ['7', '30'], true), fn ($q) => $q->where('movimientos_inventario.created_at', '>=', now()->subDays((int) $periodo - 1)->startOfDay()))
            ->when(!$periodo && $request->filled('desde'), fn ($q) => $q->where('movimientos_inventario.created_at', '>=', \Carbon\Carbon::parse($request->desde)->startOfDay()))
            ->when(!$periodo && $request->filled('hasta'), fn ($q) => $q->where('movimientos_inventario.created_at', '<=', \Carbon\Carbon::parse($request->hasta)->endOfDay()));

        // Resumen del periodo: cuántos movimientos y cuánto valen (cantidad × costo actual del insumo)
        $resumen = (clone $query)->join('materia_prima', 'materia_prima.id', '=', 'movimientos_inventario.id_materia')
            ->selectRaw('movimientos_inventario.tipo as tipo, COUNT(*) as n, SUM(movimientos_inventario.cantidad * materia_prima.costo_unitario) as valor')
            ->groupBy('movimientos_inventario.tipo')->get()->keyBy('tipo');
        $porMotivo = (clone $query)->selectRaw('motivo, COUNT(*) as n')->groupBy('motivo')->pluck('n', 'motivo');

        $movimientos = $query->with(['materia.unidad', 'usuario'])
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(30)->withQueryString();
        $materia = $request->filled('id_materia') ? MateriaPrima::find($request->id_materia) : null;

        return view('movimientos.index', compact('movimientos', 'resumen', 'porMotivo', 'periodo', 'materia'));
    }
}
