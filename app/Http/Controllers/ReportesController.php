<?php
namespace App\Http\Controllers;

use App\Support\TablaFiltro;
use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\KardexProducto;
use App\Exports\StockExport;
use App\Exports\KardexExport;
use App\Support\KardexCalculo;
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
        $u = auth()->user();

        // Stock
        $insumosStockBajo = MateriaPrima::where('estado', 'activo')->whereColumn('stock_actual', '<=', 'stock_minimo')->count();
        $productosStockBajo = Producto::where('estado', 'activo')->whereColumn('stock_actual', '<=', 'stock_minimo')->count();
        $valorInsumos = (float) MateriaPrima::where('estado', 'activo')->selectRaw('SUM(stock_actual * costo_unitario) as v')->value('v');
        $valorProductos = (float) Producto::where('estado', 'activo')->selectRaw('SUM(stock_actual * COALESCE(costo_produccion, 0)) as v')->value('v');
        $movimientosHoy = MovimientoInventario::whereDate('created_at', today())->count();

        // Cierre diario: hoy y días con movimiento que quedaron sin cerrar (últimos 7)
        $cierreHoy = \App\Models\CierreInventario::with('usuario')->whereDate('fecha', today())->first();
        $diasConMov = MovimientoInventario::where('created_at', '>=', today()->subDays(7))->where('created_at', '<', today())
            ->get(['created_at'])->map(fn ($m) => $m->created_at->toDateString())->unique();
        $cerrados = \App\Models\CierreInventario::whereDate('fecha', '>=', today()->subDays(7))->get()->map(fn ($c) => $c->fecha->toDateString());
        $sinCerrar = $diasConMov->diff($cerrados)->count();

        // Mes actual: mermas y compras
        $inicioMes = today()->startOfMonth();
        $mermasMes = (float) MovimientoInventario::join('materia_prima', 'materia_prima.id', '=', 'movimientos_inventario.id_materia')
            ->where('movimientos_inventario.motivo', 'merma')->where('movimientos_inventario.created_at', '>=', $inicioMes)
            ->sum(\Illuminate\Support\Facades\DB::raw('movimientos_inventario.cantidad * materia_prima.costo_unitario'));
        $comprasMes = (float) \App\Models\Compra::where('estado', '!=', 'anulada')->whereDate('fecha_compra', '>=', $inicioMes)->sum('total');
        $comprasPorRecibir = \App\Models\Compra::where('estado', 'pendiente')->count();
        $ventasMesQ = \App\Models\Venta::where('estado', 'completada')->where('fecha_venta', '>=', $inicioMes);
        $ventasMes = (float) (clone $ventasMesQ)->sum('total');
        $ventasMesN = (clone $ventasMesQ)->count();

        // PER del último conteo (insumos con diferencia ÷ contados × 100)
        $ultimoLote = \App\Models\ConteoFisico::orderByDesc('created_at')->orderByDesc('id')->value('lote');
        $per = null; $fechaConteo = null;
        if ($ultimoLote) {
            $filas = \App\Models\ConteoFisico::where('lote', $ultimoLote)->get();
            $per = $filas->count() ? round($filas->filter(fn ($f) => abs((float) $f->diferencia) > 0.0005)->count() / $filas->count() * 100, 1) : null;
            $fechaConteo = $filas->first()?->created_at;
        }

        return view('reportes.index', compact(
            'insumosStockBajo', 'productosStockBajo', 'valorInsumos', 'valorProductos', 'movimientosHoy',
            'cierreHoy', 'sinCerrar', 'mermasMes', 'comprasMes', 'comprasPorRecibir', 'ventasMes', 'ventasMesN', 'per', 'fechaConteo'
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
    /** Órdenes del reporte de stock: su sentido natural y cómo se lee cada sentido. */
    public const ORDENES_STOCK = [
        ''       => ['texto' => 'Urgencia',          'dir' => 'asc',  'asc' => 'agotados primero',   'desc' => 'normales primero',   'icono' => 'fa-fire'],
        'nombre' => ['texto' => 'Nombre',            'dir' => 'asc',  'asc' => 'A-Z',                'desc' => 'Z-A',                'icono' => 'fa-font'],
        'dias'   => ['texto' => 'Días que alcanza',  'dir' => 'asc',  'asc' => 'menos días primero', 'desc' => 'más días primero',   'icono' => 'fa-hourglass-half'],
        'stock'  => ['texto' => 'Cantidad en stock', 'dir' => 'asc',  'asc' => 'menor primero',      'desc' => 'mayor primero',      'icono' => 'fa-cubes'],
        'valor'  => ['texto' => 'Valor',             'dir' => 'desc', 'asc' => 'menor primero',      'desc' => 'mayor primero',      'icono' => 'fa-coins'],
    ];

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

        // Días que alcanza el stock de cada insumo (consumo de los últimos 14 días)
        $consumo = MateriaPrima::consumoDiario($materias->pluck('id'));
        $materias->each(function ($m) use ($consumo) {
            $m->por_dia = $consumo[$m->id] ?? null;
            $m->dias = $m->diasCobertura($m->por_dia);
        });

        // Orden elegido (por defecto: los más urgentes primero). Cada orden tiene su sentido
        // natural (A-Z, menos días, mayor valor…) y con dir se puede invertir.
        $orden = array_key_exists($request->get('orden'), self::ORDENES_STOCK) ? $request->get('orden') : '';
        $dir = in_array($request->get('dir'), ['asc', 'desc'], true) ? $request->get('dir') : self::ORDENES_STOCK[$orden]['dir'];
        $clave = match ($orden) {
            'nombre' => fn ($i) => mb_strtolower($i->nombre),
            'stock'  => fn ($i) => (float) $i->stock_actual,
            'dias'   => fn ($i) => $i->dias ?? ($dir === 'desc' ? -1 : PHP_INT_MAX),   // sin consumo: siempre al final
            'valor'  => fn ($i) => (float) $i->valor,
            default  => null,
        };
        if ($clave) {
            $ordenar = fn ($c) => ($dir === 'desc' ? $c->sortByDesc($clave) : $c->sortBy($clave))->values();
            $materias = $ordenar($materias);
            // los productos no tienen "días que alcanza": se dejan por urgencia
            if ($orden !== 'dias') $productos = $ordenar($productos);
        } elseif ($dir === 'desc') {
            $materias = $materias->reverse()->values();
            $productos = $productos->reverse()->values();
        }

        // Cómo se está viendo la lista (sale igual en el PDF y en el Excel)
        $vista = collect([
            ['todos' => 'Todo el inventario', 'materia' => 'Solo materia prima', 'producto' => 'Solo productos'][$tipo] ?? 'Todo el inventario',
            ['bajo' => 'Solo los que necesitan reposición', 'agotado' => 'Solo agotados'][$filtroStock] ?? 'Todos los niveles',
            'Ordenado por ' . mb_strtolower(self::ORDENES_STOCK[$orden]['texto']) . ' (' . self::ORDENES_STOCK[$orden][$dir] . ')',
            $request->filled('buscar') ? 'Búsqueda: «' . $request->buscar . '»' : null,
        ])->filter()->implode(' · ');

        $valorMateria  = $materias->sum('valor');
        $valorProducto = $productos->sum('valor');

        return compact('tipo', 'materias', 'productos', 'valorMateria', 'valorProducto', 'orden', 'dir', 'vista');
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
    public const MOTIVOS_KARDEX = [
        'compra' => 'Compra', 'produccion' => 'Producción', 'venta' => 'Venta', 'merma' => 'Merma',
        'ajuste_manual' => 'Ajuste de inventario', 'devolucion' => 'Devolución', 'conteo_fisico' => 'Conteo físico',
        'anulacion' => 'Anulación',
    ];

    /** Columnas de la tabla del kardex (claves iguales a las de data-clave en la vista). */
    private static function columnasKardex(): array
    {
        return [
            'fecha'   => ['titulo' => 'Fecha',   'valor' => fn ($m) => $m->created_at->timestamp * 1000000 + $m->id, 'texto' => fn ($m) => $m->created_at->format('d/m/Y H:i')],
            'tipo'    => ['titulo' => 'Tipo',    'valor' => fn ($m) => $m->tipo_txt],
            'motivo'  => ['titulo' => 'Motivo',  'valor' => fn ($m) => $m->motivo_txt],
            'entrada' => ['titulo' => 'Entrada', 'valor' => fn ($m) => (float) $m->entrada, 'texto' => fn ($m) => ''],
            'salida'  => ['titulo' => 'Salida',  'valor' => fn ($m) => (float) $m->salida, 'texto' => fn ($m) => ''],
            'saldo'   => ['titulo' => 'Saldo',   'valor' => fn ($m) => (float) $m->stock_despues, 'texto' => fn ($m) => ''],
            'usuario' => ['titulo' => 'Usuario', 'valor' => fn ($m) => $m->usuario_txt],
            'detalle' => ['titulo' => 'Detalle', 'valor' => fn ($m) => (string) $m->observacion],
        ];
    }

    private function datosKardex(Request $request)
    {
        $materias  = MateriaPrima::with('unidad')->orderBy('nombre')->get();
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
                    $n = KardexCalculo::normalizar($m->stock_antes, $m->stock_despues, $m->tipo, $m->cantidad, $m->motivo);
                    $m->setAttribute('entrada', $n['entrada']);
                    $m->setAttribute('salida', $n['salida']);
                    $m->setAttribute('es_ajuste', $n['es_ajuste']);
                    return $m;
                });
            }
        }

        $unidad = $item
            ? ($tipoItem === 'materia' ? ($item->unidad->abreviatura ?? '') : 'uds')
            : '';

        // Resumen del periodo (lo que el almacenero realmente quiere saber)
        $resumen = KardexCalculo::resumir(
            $movimientos->map(fn($m) => [
                'antes'   => $m->stock_antes !== null ? (float) $m->stock_antes : null,
                'despues' => (float) $m->stock_despues,
                'entrada' => $m->entrada,
                'salida'  => $m->salida,
            ])->values()->all(),
            $item ? (float) $item->stock_actual : 0.0
        );
        $totalEntradas = $resumen['totalEntradas'];
        $totalSalidas  = $resumen['totalSalidas'];
        $saldoInicial  = $resumen['saldoInicial'];
        $saldoFinal    = $resumen['saldoFinal'];
        $cuadra        = $resumen['cuadra'];

        // Desglose por motivo: de dónde entra y a dónde sale el stock
        $porMotivo = $movimientos->groupBy('motivo')->map(fn($g) => [
            'entrada' => $g->sum('entrada'),
            'salida'  => $g->sum('salida'),
            'cuenta'  => $g->count(),
        ])->sortByDesc(fn($d) => $d['entrada'] + $d['salida']);

        // Aviso de stock bajo: que el almacenero lo vea justo en la ficha del ítem.
        $stockBajo = $item ? ($item->stock_actual <= $item->stock_minimo) : false;

        // Textos de cada fila (los mismos en pantalla, PDF y Excel) y el orden/filtros que eligió
        // el usuario en la tabla, para que el PDF y el Excel salgan igual que la pantalla.
        $movimientos->each(function ($m) {
            $m->setAttribute('tipo_txt', $m->es_ajuste ? 'Ajuste' : ucfirst($m->tipo));
            $m->setAttribute('motivo_txt', self::MOTIVOS_KARDEX[$m->motivo] ?? ucfirst(str_replace('_', ' ', (string) $m->motivo)));
            $m->setAttribute('usuario_txt', $m->usuario->apodo ?? $m->usuario->nombre ?? '—');
        });
        $columnas = self::columnasKardex();
        $movimientosVista = TablaFiltro::aplicar($movimientos, $request, $columnas);
        $filtroTabla = TablaFiltro::descripcion($request, $columnas);

        return compact(
            'materias', 'productos', 'item', 'movimientos', 'tipoItem', 'idItem', 'unidad',
            'desde', 'hasta', 'periodo', 'costo', 'stockBajo',
            'totalEntradas', 'totalSalidas', 'saldoInicial', 'saldoFinal', 'cuadra', 'porMotivo',
            'movimientosVista', 'filtroTabla'
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
            new KardexExport($datos['item'], $datos['movimientosVista'], $datos['unidad'], $datos['saldoInicial'], $datos['filtroTabla']),
            'kardex-' . str($datos['item']->nombre)->slug() . '.xlsx'
        );
    }
}
