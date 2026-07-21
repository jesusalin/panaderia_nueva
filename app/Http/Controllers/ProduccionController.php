<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\KardexProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduccionController extends Controller
{
    // ─── LISTADO ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Produccion::with(['producto.categoria', 'usuario'])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('id_producto'))
            $query->where('id_producto', $request->id_producto);
        if ($request->filled('fecha_desde'))
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))
            $query->whereDate('fecha', '<=', $request->fecha_hasta);

        $stats = [
            'total'    => (clone $query)->count(),
            'unidades' => (clone $query)->sum('cantidad'),
            'hoy'      => (clone $query)->whereDate('fecha', now())->count(),
            'top'      => (clone $query)->select('id_producto', DB::raw('SUM(cantidad) as total'))
                            ->groupBy('id_producto')->orderByDesc('total')->with('producto')->first(),
        ];

        $producciones = $query->paginate(15)->withQueryString();
        $productos    = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('produccion.index', compact('producciones', 'productos', 'stats'));
    }

    // ─── FORMULARIO NUEVA PRODUCCIÓN ─────────────────────────
    public function create()
    {
        $productos = Producto::where('estado', 'activo')
            ->with(['receta.detalles.materia.unidad', 'categoria'])
            ->orderBy('nombre')
            ->get();

        return view('produccion.create', compact('productos'));
    }

    // ─── GUARDAR PRODUCCIÓN ──────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'id_producto'  => 'required|exists:productos,id',
            'cantidad'     => 'required|integer|min:1',
            'fecha'        => 'required|date',
            'observacion'  => 'nullable|string|max:500',
        ], [
            'id_producto.required' => 'Selecciona un producto.',
            'cantidad.required'    => 'La cantidad es obligatoria.',
            'cantidad.min'         => 'La cantidad debe ser al menos 1.',
            'fecha.required'       => 'La fecha es obligatoria.',
        ]);

        $producto = Producto::with('receta.detalles.materia')->findOrFail($request->id_producto);

        if (!$producto->receta) {
            return back()->withErrors(['id_producto' => 'Este producto no tiene receta registrada.'])->withInput();
        }

        // Calcular cuánta materia prima se necesita
        $receta   = $producto->receta;
        $lotes    = $request->cantidad / max($receta->rendimiento, 1);
        $detalles = $receta->detalles;

        // Verificar stock suficiente antes de descontar
        foreach ($detalles as $detalle) {
            $necesario = $detalle->cantidad * $lotes;
            if ($detalle->materia->stock_actual < $necesario) {
                return back()->withErrors([
                    'id_producto' => "Stock insuficiente de \"{$detalle->materia->nombre}\". 
                        Necesitas {$necesario} {$detalle->materia->unidad->abreviatura}, 
                        disponible: {$detalle->materia->stock_actual} {$detalle->materia->unidad->abreviatura}."
                ])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Registrar la producción
            $produccion = Produccion::create([
                'id_producto' => $producto->id,
                'id_usuario'  => auth()->id(),
                'cantidad'    => $request->cantidad,
                'fecha'       => $request->fecha,
                'observacion' => $request->observacion,
            ]);

            // Descontar materia prima y registrar movimientos
            foreach ($detalles as $detalle) {
                $necesario    = round($detalle->cantidad * $lotes, 3);
                $materia      = MateriaPrima::findOrFail($detalle->id_materia);
                $stockAntes   = $materia->stock_actual;
                $stockDespues = round($stockAntes - $necesario, 3);

                $materia->update(['stock_actual' => $stockDespues]);

                MovimientoInventario::create([
                    'id_materia'    => $materia->id,
                    'id_usuario'    => auth()->id(),
                    'tipo'          => 'salida',
                    'motivo'        => 'produccion',
                    'referencia_id' => $produccion->id,
                    'cantidad'      => $necesario,
                    'stock_antes'   => $stockAntes,
                    'stock_despues' => $stockDespues,
                    'observacion'   => "Producción #{$produccion->id} — {$producto->nombre}",
                ]);
            }

            // Sumar al stock del producto y registrar en kardex
            $stockAntesProd = $producto->stock_actual;
            $producto->increment('stock_actual', $request->cantidad);
            KardexProducto::create([
                'id_producto'   => $producto->id,
                'id_usuario'    => auth()->id(),
                'tipo'          => 'entrada',
                'motivo'        => 'produccion',
                'referencia_id' => $produccion->id,
                'cantidad'      => $request->cantidad,
                'stock_antes'   => $stockAntesProd,
                'stock_despues' => $stockAntesProd + $request->cantidad,
                'observacion'   => "Producción #{$produccion->id}",
            ]);

            DB::commit();
            return redirect()->route('produccion.index')
                ->with('success', "Producción registrada. Se agregaron {$request->cantidad} unidades de \"{$producto->nombre}\" al stock.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar: ' . $e->getMessage()])->withInput();
        }
    }

    // ─── DETALLE ─────────────────────────────────────────────
    public function show(Produccion $produccion)
    {
        $produccion->load(['producto.receta.detalles.materia.unidad', 'usuario']);
        return view('produccion.show', compact('produccion'));
    }

    // ─── ELIMINAR (revirtiendo el stock que generó) ──────────
    public function destroy(Produccion $produccion)
    {
        $producto = $produccion->producto;

        // Si parte de lo producido ya se vendió o se movió, revertir dejaría el
        // stock en negativo. En ese caso no se puede deshacer la producción.
        if ($producto->stock_actual < $produccion->cantidad) {
            return back()->withErrors([
                'error' => "No se puede eliminar esta producción: ya se vendieron o movieron unidades de \"{$producto->nombre}\" desde entonces. " .
                           "Stock actual: {$producto->stock_actual}, se necesitarían revertir {$produccion->cantidad}. " .
                           "El stock quedaría negativo.",
            ]);
        }

        DB::beginTransaction();
        try {
            // Revertir el stock del producto que se había sumado
            $stockAntesProd = $producto->stock_actual;
            $producto->decrement('stock_actual', $produccion->cantidad);
            KardexProducto::create([
                'id_producto'   => $producto->id,
                'id_usuario'    => auth()->id(),
                'tipo'          => 'salida',
                'motivo'        => 'devolucion',
                'referencia_id' => null,
                'cantidad'      => $produccion->cantidad,
                'stock_antes'   => $stockAntesProd,
                'stock_despues' => $stockAntesProd - $produccion->cantidad,
                'observacion'   => "Eliminación de producción #{$produccion->id} (registrada por error)",
            ]);

            // Devolver la materia prima que se había descontado para esta producción
            $movimientos = MovimientoInventario::where('motivo', 'produccion')
                ->where('referencia_id', $produccion->id)->get();

            foreach ($movimientos as $mov) {
                $materia = MateriaPrima::find($mov->id_materia);
                if (!$materia) continue;

                $stockAntes   = $materia->stock_actual;
                $stockDespues = round($stockAntes + $mov->cantidad, 3);
                $materia->update(['stock_actual' => $stockDespues]);

                MovimientoInventario::create([
                    'id_materia'    => $materia->id,
                    'id_usuario'    => auth()->id(),
                    'tipo'          => 'entrada',
                    'motivo'        => 'devolucion',
                    'referencia_id' => null,
                    'cantidad'      => $mov->cantidad,
                    'stock_antes'   => $stockAntes,
                    'stock_despues' => $stockDespues,
                    'observacion'   => "Devuelto por eliminación de producción #{$produccion->id}",
                ]);
            }

            $productoNombre = $producto->nombre;
            $produccion->delete();

            DB::commit();
            return redirect()->route('produccion.index')
                ->with('success', "Producción de \"{$productoNombre}\" eliminada. El stock del producto y de los insumos se revirtió correctamente.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    }

    // ─── RECETAS ─────────────────────────────────────────────
    public function recetas()
    {
        $productos = Producto::where('estado', 'activo')
            ->with(['receta.detalles.materia.unidad', 'categoria'])
            ->orderBy('nombre')
            ->get();

        $stats = [
            'total'       => $productos->count(),
            'con_receta'  => $productos->filter(fn($p) => $p->receta)->count(),
            'sin_receta'  => $productos->filter(fn($p) => !$p->receta)->count(),
        ];

        return view('produccion.recetas', compact('productos', 'stats'));
    }

    public function crearReceta(Request $request)
    {
        $request->validate([
            'id_producto'       => 'required|exists:productos,id',
            'rendimiento'       => 'required|integer|min:1',
            'descripcion'       => 'nullable|string|max:500',
            'ingredientes'      => 'required|array|min:1',
            'ingredientes.*.id_materia' => 'required|exists:materia_prima,id',
            'ingredientes.*.cantidad'   => 'required|numeric|min:0.001',
        ], [
            'id_producto.required'   => 'Selecciona un producto.',
            'rendimiento.required'   => 'El rendimiento es obligatorio.',
            'ingredientes.required'  => 'Agrega al menos un ingrediente.',
        ]);

        DB::beginTransaction();
        try {
            // Actualizar o crear receta
            $receta = Receta::updateOrCreate(
                ['id_producto' => $request->id_producto],
                [
                    'rendimiento' => $request->rendimiento,
                    'descripcion' => $request->descripcion,
                ]
            );

            // Reemplazar ingredientes
            RecetaDetalle::where('id_receta', $receta->id)->delete();
            foreach ($request->ingredientes as $ing) {
                RecetaDetalle::create([
                    'id_receta'  => $receta->id,
                    'id_materia' => $ing['id_materia'],
                    'cantidad'   => $ing['cantidad'],
                ]);
            }

            DB::commit();
            return redirect()->route('produccion.recetas')
                ->with('success', 'Receta guardada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    // ─── API: obtener ingredientes de un producto (para JS) ──
    public function ingredientes(Producto $producto)
    {
        $receta = $producto->receta()->with('detalles.materia.unidad')->first();
        if (!$receta) return response()->json(null);

        return response()->json([
            'rendimiento' => $receta->rendimiento,
            'descripcion' => $receta->descripcion,
            'detalles'    => $receta->detalles->map(fn($d) => [
                'nombre'       => $d->materia->nombre,
                'cantidad'     => $d->cantidad,
                'abreviatura'  => $d->materia->unidad->abreviatura,
                'stock_actual' => $d->materia->stock_actual,
            ]),
        ]);
    }
}
