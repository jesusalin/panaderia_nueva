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
                if ($request->filled('fecha_desde')) $query->whereDate('created_at', '>=', $request->fecha_desde);
                if ($request->filled('fecha_hasta')) $query->whereDate('created_at', '<=', $request->fecha_hasta);
                $movimientos = $query->get();
            }
        }

        $unidad = $item
            ? ($tipoItem === 'materia' ? ($item->unidad->abreviatura ?? '') : 'uds')
            : '';

        return compact('materias', 'productos', 'item', 'movimientos', 'tipoItem', 'idItem', 'unidad');
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
