<?php
namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\KardexProducto;
use App\Exports\StockExport;
use App\Exports\KardexExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportesController extends Controller
{
    /**
     * Página de entrada a Reportes: en vez de obligar al almacenero a saber
     * en cuál sub-menú está cada reporte, aquí ve todos de un vistazo, con
     * un par de cifras clave que ya le dicen si necesita entrar a revisar algo.
     */
    public function index()
    {
        $insumosStockBajo = MateriaPrima::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        $productosStockBajo = Producto::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        $movimientosHoy = MovimientoInventario::whereDate('created_at', now())->count();

        // La exactitud del último conteo es el PROMEDIO de todas las filas de su
        // lote (un conteo cuenta varios insumos a la vez), no la de una sola fila.
        $ultimoLote = \App\Models\ConteoFisico::orderByDesc('created_at')->value('lote');
        $exactitudUltimoConteo = $ultimoLote
            ? round(\App\Models\ConteoFisico::where('lote', $ultimoLote)->get()->avg('exactitud'), 1)
            : null;

        return view('reportes.index', compact(
            'insumosStockBajo', 'productosStockBajo', 'movimientosHoy', 'exactitudUltimoConteo'
        ));
    }

    // ══════════════════════════ REPORTE DE STOCK ══════════════════════════

    /**
     * Reunimos materia prima y productos con su valorización (stock x costo),
     * aplicando los mismos filtros que se usarán también en el PDF y el Excel.
     *
     * Cada ítem recibe dos datos calculados para que el almacenero no tenga
     * que hacer cuentas:
     *   - nivel:   agotado | bajo | ok  (semáforo)
     *   - reponer: cantidad sugerida a comprar/producir para volver a un
     *              nivel seguro (configurada o el doble del mínimo).
     * Por defecto los ítems críticos salen primero.
     */
    private function datosStock(Request $request)
    {
        $tipo = $request->get('tipo', 'todos'); // todos | materia | producto
        $filtroStock = $request->get('stock');  // '' | bajo | agotado

        $materias = collect();
        $productos = collect();

        if ($tipo !== 'producto') {
            $materias = MateriaPrima::with(['unidad', 'proveedor'])
                ->where('estado', 'activo')
                ->when($request->filled('buscar'), fn($q) => $q->where('nombre', 'like', '%' . $request->buscar . '%'))
                ->get();
        }

        if ($tipo !== 'materia') {
            $productos = Producto::with('categoria')
                ->where('estado', 'activo')
                ->when($request->filled('buscar'), fn($q) => $q->where('nombre', 'like', '%' . $request->buscar . '%'))
                ->get();
        }

        $clasificar = function ($item, bool $esMateria) {
            $stock = (float) $item->stock_actual;
            $min   = (float) $item->stock_minimo;

            $item->nivel = $stock <= 0 ? 'agotado' : ($stock <= $min ? 'bajo' : 'ok');

            $objetivo = $esMateria && $item->cantidad_reposicion
                ? $stock + (float) $item->cantidad_reposicion
                : $min * 2;
            $item->reponer = $item->nivel === 'ok' ? 0 : max($objetivo - $stock, 0);

            $costo = $esMateria ? $item->costo_unitario : ($item->costo_produccion ?? 0);
            $item->valor = $stock * (float) $costo;
            return $item;
        };

        $orden = ['agotado' => 0, 'bajo' => 1, 'ok' => 2];
        $preparar = function ($coleccion, bool $esMateria) use ($clasificar, $orden, $filtroStock) {
            return $coleccion
                ->map(fn($i) => $clasificar($i, $esMateria))
                ->filter(function ($i) use ($filtroStock) {
                    if ($filtroStock === 'bajo')    return $i->nivel !== 'ok';      // bajo + agotado
                    if ($filtroStock === 'agotado') return $i->nivel === 'agotado';
                    return true;
                })
                ->sortBy(fn($i) => $orden[$i->nivel] . '|' . mb_strtolower($i->nombre))
                ->values();
        };

        $materias  = $preparar($materias, true);
        $productos = $preparar($productos, false);

        $valorMateria  = $materias->sum('valor');
        $valorProducto = $productos->sum('valor');

        return compact('tipo', 'materias', 'productos', 'valorMateria', 'valorProducto');
    }

    public function stock(Request $request)
    {
        return view('reportes.stock', $this->datosStock($request));
    }

    public function stockPdf(Request $request)
    {
        $datos = $this->datosStock($request);
        $pdf = Pdf::loadView('reportes.stock-pdf', $datos)->setPaper('a4');
        return $pdf->stream('reporte-stock-' . now()->format('Y-m-d') . '.pdf');
    }

    public function stockExcel(Request $request)
    {
        $datos = $this->datosStock($request);
        return Excel::download(
            new StockExport($datos['materias'], $datos['productos']),
            'reporte-stock-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // ══════════════════════════ REPORTE DE KARDEX ══════════════════════════

    /** Periodos rápidos del selector (clave => etiqueta). */
    public const PERIODOS = [
        'hoy'  => 'Hoy',
        '7'    => 'Últimos 7 días',
        '30'   => 'Últimos 30 días',
        'mes'  => 'Este mes',
        'todo' => 'Todo el historial',
    ];

    /**
     * Resuelve el rango de fechas. Si el usuario escribió fechas propias se
     * respetan; si no, se usa el periodo rápido (por defecto 30 días).
     */
    private function rangoPeriodo(Request $request): array
    {
        if ($request->filled('fecha_desde') || $request->filled('fecha_hasta')) {
            return [
                $request->filled('fecha_desde') ? Carbon::parse($request->fecha_desde)->startOfDay() : null,
                $request->filled('fecha_hasta') ? Carbon::parse($request->fecha_hasta)->endOfDay()   : null,
                'personalizado',
            ];
        }

        $periodo = $request->get('periodo', '30');
        if (!array_key_exists($periodo, self::PERIODOS)) $periodo = '30';

        $desde = match ($periodo) {
            'hoy'  => today(),
            '7'    => today()->subDays(6),
            '30'   => today()->subDays(29),
            'mes'  => today()->startOfMonth(),
            default => null,
        };

        return [$desde, $desde ? now()->endOfDay() : null, $periodo];
    }

    /** Lee el ítem elegido: "materia-5" / "producto-3" (o los parámetros antiguos). */
    private function itemSolicitado(Request $request): array
    {
        if ($request->filled('item') && str_contains($request->item, '-')) {
            [$tipo, $id] = explode('-', $request->item, 2);
            return [$tipo, (int) $id];
        }
        return [$request->get('tipo_item'), $request->get('id_item')];
    }

    /**
     * El kardex es POR ítem (así se enseña en contabilidad/gestión de
     * inventarios): se elige un insumo o un producto y se ve su ficha
     * cronológica de entradas, salidas y saldo.
     *
     * Cada movimiento se normaliza con "entrada" y "salida" calculadas a
     * partir de la variación real del stock (stock_despues - stock_antes).
     * Así los AJUSTES y conteos físicos —que se guardan con tipo "ajuste"—
     * también aparecen como entrada o salida y entran en los totales.
     */
    private function datosKardex(Request $request)
    {
        $materias  = MateriaPrima::orderBy('nombre')->get();
        $productos = Producto::orderBy('nombre')->get();

        [$tipoItem, $idItem] = $this->itemSolicitado($request);
        [$desde, $hasta, $periodo] = $this->rangoPeriodo($request);

        $item = null;
        $movimientos = collect();
        $costo = 0;

        if ($tipoItem && $idItem) {
            if ($tipoItem === 'materia') {
                $item = MateriaPrima::with('unidad')->find($idItem);
                $costo = $item->costo_unitario ?? 0;
                $query = $item ? MovimientoInventario::with('usuario')->where('id_materia', $item->id) : null;
            } else {
                $item = Producto::find($idItem);
                $costo = $item->costo_produccion ?? 0;
                $query = $item ? KardexProducto::with('usuario')->where('id_producto', $item->id) : null;
            }

            if ($item && $query) {
                if ($desde) $query->where('created_at', '>=', $desde);
                if ($hasta) $query->where('created_at', '<=', $hasta);

                $movimientos = $query->orderBy('created_at')->orderBy('id')->get()->map(function ($m) {
                    $delta = $m->stock_antes !== null
                        ? (float) $m->stock_despues - (float) $m->stock_antes
                        : ($m->tipo === 'entrada' ? (float) $m->cantidad : -(float) $m->cantidad);

                    $m->setAttribute('entrada', $delta > 0 ? $delta : 0);
                    $m->setAttribute('salida',  $delta < 0 ? -$delta : 0);
                    $m->setAttribute('es_ajuste', $m->tipo === 'ajuste' || $m->motivo === 'ajuste_manual');
                    return $m;
                });
            }
        }

        $unidad = $item
            ? ($tipoItem === 'materia' ? ($item->unidad->abreviatura ?? '') : 'uds')
            : '';

        // Resumen del periodo (lo que el almacenero realmente quiere saber)
        $totalEntradas = $movimientos->sum('entrada');
        $totalSalidas  = $movimientos->sum('salida');
        $primero = $movimientos->first();
        $saldoInicial = $primero
            ? ($primero->stock_antes !== null ? (float) $primero->stock_antes : (float) $primero->stock_despues - $primero->entrada + $primero->salida)
            : ($item ? (float) $item->stock_actual : 0);
        $saldoFinal = $movimientos->isNotEmpty() ? (float) $movimientos->last()->stock_despues : $saldoInicial;

        // ¿El kardex "cuadra"? saldo inicial + entradas - salidas = saldo final
        $cuadra = abs(($saldoInicial + $totalEntradas - $totalSalidas) - $saldoFinal) < 0.01;

        // Desglose por motivo: de dónde entra y a dónde sale el stock
        $porMotivo = $movimientos->groupBy('motivo')->map(fn($g) => [
            'entrada' => $g->sum('entrada'),
            'salida'  => $g->sum('salida'),
            'cuenta'  => $g->count(),
        ])->sortByDesc(fn($d) => $d['entrada'] + $d['salida']);

        // Aviso de stock bajo: que el almacenero lo vea justo en la ficha del ítem.
        $stockBajo = $item ? ($item->stock_actual <= $item->stock_minimo) : false;

        return compact(
            'materias', 'productos', 'item', 'movimientos', 'tipoItem', 'idItem', 'unidad',
            'desde', 'hasta', 'periodo', 'costo', 'stockBajo',
            'totalEntradas', 'totalSalidas', 'saldoInicial', 'saldoFinal', 'cuadra', 'porMotivo'
        );
    }

    public function kardex(Request $request)
    {
        return view('reportes.kardex', $this->datosKardex($request));
    }

    public function kardexPdf(Request $request)
    {
        $datos = $this->datosKardex($request);
        if (!$datos['item']) return back()->with('error', 'Selecciona un ítem para generar el reporte.');

        $pdf = Pdf::loadView('reportes.kardex-pdf', $datos)->setPaper('a4');
        return $pdf->stream('kardex-' . str($datos['item']->nombre)->slug() . '.pdf');
    }

    public function kardexExcel(Request $request)
    {
        $datos = $this->datosKardex($request);
        if (!$datos['item']) return back()->with('error', 'Selecciona un ítem para generar el reporte.');

        return Excel::download(
            new KardexExport($datos['item'], $datos['movimientos'], $datos['unidad'], $datos['saldoInicial']),
            'kardex-' . str($datos['item']->nombre)->slug() . '.xlsx'
        );
    }
}
