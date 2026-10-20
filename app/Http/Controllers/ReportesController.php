<?php
namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\KardexProducto;
use App\Exports\StockExport;
use App\Exports\KardexExport;
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
     */
    private function datosStock(Request $request)
    {
        $tipo = $request->get('tipo', 'todos'); // todos | materia | producto

        $materias = collect();
        $productos = collect();

        if ($tipo !== 'producto') {
            $materias = MateriaPrima::with(['unidad', 'proveedor'])
                ->where('estado', 'activo')
                ->when($request->filled('buscar'), fn($q) => $q->where('nombre', 'like', '%' . $request->buscar . '%'))
                ->when($request->get('stock') === 'bajo', fn($q) => $q->whereColumn('stock_actual', '<=', 'stock_minimo'))
                ->orderBy('nombre')->get();
        }

        if ($tipo !== 'materia') {
            $productos = Producto::with('categoria')
                ->where('estado', 'activo')
                ->when($request->filled('buscar'), fn($q) => $q->where('nombre', 'like', '%' . $request->buscar . '%'))
                ->when($request->get('stock') === 'bajo', fn($q) => $q->whereColumn('stock_actual', '<=', 'stock_minimo'))
                ->orderBy('nombre')->get();
        }

        $valorMateria  = $materias->sum(fn($m) => $m->stock_actual * $m->costo_unitario);
        $valorProducto = $productos->sum(fn($p) => $p->stock_actual * ($p->costo_produccion ?? 0));

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

    /**
     * El kardex es POR ítem (así se enseña en contabilidad/gestión de
     * inventarios): se elige un insumo o un producto y se ve su ficha
     * cronológica de entradas, salidas y saldo.
     */
    private function datosKardex(Request $request)
    {
        $materias  = MateriaPrima::orderBy('nombre')->get();
        $productos = Producto::orderBy('nombre')->get();

        $item = null;
        $movimientos = collect();
        $tipoItem = $request->get('tipo_item');
        $idItem   = $request->get('id_item');

        // Si el almacenero no especifica un rango de fechas, mostramos
        // automáticamente los últimos 30 días en vez de todo el historial
        // (más rápido de leer) o nada (confuso la primera vez que se usa).
        $sinFiltroFecha = !$request->filled('fecha_desde') && !$request->filled('fecha_hasta');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        if ($sinFiltroFecha) {
            $fechaDesde = now()->subDays(30)->format('Y-m-d');
        }

        if ($tipoItem && $idItem) {
            if ($tipoItem === 'materia') {
                $item = MateriaPrima::with('unidad')->find($idItem);
                if ($item) {
                    $query = MovimientoInventario::with('usuario')
                        ->where('id_materia', $item->id)->orderBy('created_at');
                }
            } else {
                $item = Producto::find($idItem);
                if ($item) {
                    $query = KardexProducto::with('usuario')
                        ->where('id_producto', $item->id)->orderBy('created_at');
                }
            }

            if ($item) {
                if ($fechaDesde) $query->whereDate('created_at', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('created_at', '<=', $fechaHasta);
                $movimientos = $query->get();
            }
        }

        $unidad = $item
            ? ($tipoItem === 'materia' ? ($item->unidad->abreviatura ?? '') : 'uds')
            : '';

        // Aviso de stock bajo: útil para que el almacenero vea de un vistazo,
        // justo en la ficha del kardex, si este ítem necesita reposición.
        $stockBajo = $item ? ($item->stock_actual <= $item->stock_minimo) : false;

        return compact(
            'materias', 'productos', 'item', 'movimientos', 'tipoItem', 'idItem', 'unidad',
            'sinFiltroFecha', 'fechaDesde', 'fechaHasta', 'stockBajo'
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
            new KardexExport($datos['item'], $datos['movimientos'], $datos['unidad']),
            'kardex-' . str($datos['item']->nombre)->slug() . '.xlsx'
        );
    }
}
