<?php
namespace App\Http\Controllers;
use App\Models\MateriaPrima;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MateriaPrimaController extends Controller
{
    public function index(Request $request) {
        $materias = MateriaPrima::with(['unidad', 'proveedor'])
            ->withCount(['recetaDetalles', 'compraDetalles', 'movimientos', 'ordenesAutomaticas'])
            ->when($request->buscar, fn($q) => $q->where('nombre', 'like', '%'.$request->buscar.'%'))
            ->when($request->proveedor, fn($q) => $q->where('id_proveedor', $request->proveedor))
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->when($request->stock === 'bajo', fn($q) => $q->whereColumn('stock_actual', '<=', 'stock_minimo'))
            ->orderBy('nombre')->paginate(12)->withQueryString();

        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();

        return view('materia_prima.index', compact('materias', 'proveedores'));
    }

    public function create() {
        $unidades    = UnidadMedida::orderBy('nombre')->get();
        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();
        return view('materia_prima.create', compact('unidades', 'proveedores'));
    }

    public function store(Request $request) {
        $request->validate([
            'nombre'              => 'required|string|max:150',
            'id_unidad'           => 'required|exists:unidades_medida,id',
            'id_proveedor'        => 'nullable|exists:proveedores,id',
            'stock_actual'        => 'required|numeric|min:0',
            'stock_minimo'        => 'required|numeric|min:0',
            'cantidad_reposicion' => 'nullable|numeric|min:0',
            'costo_unitario'      => 'required|numeric|min:0',
            'estado'              => 'required|in:activo,inactivo'
        ]);
        MateriaPrima::create($request->all());
        return redirect()->route('materia-prima.index')->with('success', 'Materia prima creada.');
    }

    public function edit(MateriaPrima $materiaPrima) {
        $unidades    = UnidadMedida::orderBy('nombre')->get();
        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();
        return view('materia_prima.edit', compact('materiaPrima', 'unidades', 'proveedores'));
    }

    public function update(Request $request, MateriaPrima $materiaPrima) {
        $request->validate([
            'nombre'              => 'required|string|max:150',
            'id_unidad'           => 'required|exists:unidades_medida,id',
            'id_proveedor'        => 'nullable|exists:proveedores,id',
            'stock_actual'        => 'required|numeric|min:0',
            'stock_minimo'        => 'required|numeric|min:0',
            'cantidad_reposicion' => 'nullable|numeric|min:0',
            'costo_unitario'      => 'required|numeric|min:0',
            'estado'              => 'required|in:activo,inactivo'
        ]);
        $materiaPrima->update($request->all());
        return redirect()->route('materia-prima.index')->with('success', 'Materia prima actualizada.');
    }

    public function toggleEstado(MateriaPrima $materiaPrima) {
        $nuevoEstado = $materiaPrima->estado === 'activo' ? 'inactivo' : 'activo';
        $materiaPrima->update(['estado' => $nuevoEstado]);

        return redirect()->route('materia-prima.index')
            ->with('success', 'Materia prima ' . ($nuevoEstado === 'activo' ? 'activada' : 'desactivada') . '.');
    }

    public function destroy(MateriaPrima $materiaPrima) {
        // Un insumo con historial (recetas, compras, movimientos u órdenes automáticas)
        // no se puede borrar de verdad sin romper esos registros: se pide desactivarlo.
        $usos = [];
        if ($materiaPrima->recetaDetalles()->exists())      $usos[] = 'está usado en una o más recetas';
        if ($materiaPrima->compraDetalles()->exists())      $usos[] = 'tiene compras registradas';
        if ($materiaPrima->movimientos()->exists())         $usos[] = 'tiene movimientos de inventario';
        if ($materiaPrima->ordenesAutomaticas()->exists())  $usos[] = 'tiene órdenes automáticas generadas';

        if (!empty($usos)) {
            return back()->withErrors([
                'error' => "No se puede eliminar \"{$materiaPrima->nombre}\" porque " . implode(' y ', $usos) .
                           ". Usa \"Desactivar\" para que deje de estar disponible sin perder su historial.",
            ]);
        }

        $nombre = $materiaPrima->nombre;
        $materiaPrima->delete();
        return redirect()->route('materia-prima.index')->with('success', "Materia prima \"{$nombre}\" eliminada correctamente.");
    }

    /**
     * Mostrar formulario de ajuste manual de inventario.
     */
    public function ajusteForm(MateriaPrima $materiaPrima) {
        return view('materia_prima.ajuste', compact('materiaPrima'));
    }

    /**
     * Procesar el ajuste manual de inventario.
     * Permite corregir diferencias entre el stock físico real y el stock del sistema.
     */
    public function ajusteStore(Request $request, MateriaPrima $materiaPrima) {
        $request->validate([
            'stock_real'  => 'required|numeric|min:0',
            'observacion' => 'required|string|max:255',
        ]);

        $stockSistema = $materiaPrima->stock_actual;
        $stockReal    = $request->stock_real;
        $diferencia   = $stockReal - $stockSistema;

        if ($diferencia == 0) {
            return back()->with('info', 'No hay diferencia entre el stock del sistema y el stock real.');
        }

        DB::beginTransaction();
        try {
            $materiaPrima->update(['stock_actual' => $stockReal]);

            MovimientoInventario::create([
                'id_materia'    => $materiaPrima->id,
                'id_usuario'    => auth()->id(),
                'tipo'          => 'ajuste',
                'motivo'        => 'ajuste_manual',
                'referencia_id' => null,
                'cantidad'      => abs($diferencia),
                'stock_antes'   => $stockSistema,
                'stock_despues' => $stockReal,
                'observacion'   => ($diferencia > 0 ? 'Ajuste positivo (+' : 'Ajuste negativo (')
                                    . number_format($diferencia, 3) . '): ' . $request->observacion,
            ]);

            DB::commit();
            return redirect()->route('materia-prima.index')
                ->with('success', "Inventario de \"{$materiaPrima->nombre}\" ajustado correctamente.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
