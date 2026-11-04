<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\KardexProducto;
use App\Support\Cantidad;
use App\Support\PlanProduccion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'unidadesHoy' => (clone $query)->whereDate('fecha', now())->sum('cantidad'),
            'top'      => (clone $query)->reorder()
                            ->select('id_producto', DB::raw('SUM(cantidad) as total'))
                            ->groupBy('id_producto')->orderByDesc('total')->with('producto')->first(),
        ];

        $producciones = $query->paginate(15)->withQueryString();
        $productos    = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        $porProducir = PlanProduccion::sugerencias()->count();

        return view('produccion.index', compact('producciones', 'productos', 'stats', 'porProducir'));
    }

    // ─── FORMULARIO NUEVA PRODUCCIÓN ─────────────────────────
    public function create(Request $request)
    {
        $productos = Producto::where('estado', 'activo')
            ->with(['receta.detalles.materia.unidad', 'categoria'])
            ->orderBy('nombre')
            ->get();

        // Se puede llegar con el producto ya elegido (desde su ficha o desde "¿Qué producir hoy?")
        $preProducto = (int) $request->query('producto') ?: null;
        $preCantidad = $request->filled('cantidad') ? max(1, (int) $request->query('cantidad')) : null;   // sin cantidad: 1 tanda

        $sugerencias = PlanProduccion::sugerencias();

        return view('produccion.create', compact('productos', 'preProducto', 'preCantidad', 'sugerencias'));
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

        // Verificar stock suficiente antes de descontar (con un mensaje claro de lo que falta)
        $faltan = $this->faltantes($detalles, $lotes);
        if ($faltan) {
            $max = PlanProduccion::maximo($producto)['max'] ?? 0;
            return back()->withErrors([
                'id_producto' => 'No alcanzan los insumos: ' . implode('; ', $faltan) . '.'
                    . ($max > 0 ? " Con lo que hay puedes producir hasta {$max} unidades." : ''),
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Registrar la producción
            $costo = PlanProduccion::costo($producto, (int) $request->cantidad);
            $produccion = Produccion::create([
                'id_producto' => $producto->id,
                'id_usuario'  => auth()->id(),
                'cantidad'    => $request->cantidad,
                'fecha'       => $request->fecha,
                'observacion' => $request->observacion,
            ]);

            // Descontar materia prima y registrar movimientos (bloqueando cada insumo
            // para que dos producciones a la vez no dejen el stock en negativo)
            foreach ($detalles as $detalle) {
                $necesario    = round($detalle->cantidad * $lotes, 3);
                $materia      = MateriaPrima::lockForUpdate()->findOrFail($detalle->id_materia);
                if ($materia->stock_actual + 1e-9 < $necesario) {
                    throw new \RuntimeException('Otro usuario acaba de usar "' . $materia->nombre . '": ya no alcanza. Vuelve a intentarlo.');
                }
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
                ->with('success', "Producción registrada. Se agregaron {$request->cantidad} unidades de \"{$producto->nombre}\" al stock"
                    . ($costo !== null ? ' (insumos: S/ ' . number_format($costo, 2) . ').' : '.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e instanceof \RuntimeException ? $e->getMessage() : 'Error al registrar: ' . $e->getMessage()])->withInput();
        }
    }

    // ─── DETALLE ─────────────────────────────────────────────
    public function show(Produccion $produccion)
    {
        $produccion->load(['producto.categoria', 'producto.receta.detalles.materia.unidad', 'usuario']);

        // Lo que de verdad se descontó (movimientos de esta producción), no la receta actual,
        // que pudo cambiar después
        $consumos = MovimientoInventario::with('materia.unidad')
            ->where('motivo', 'produccion')->where('referencia_id', $produccion->id)->get();
        $entrada = KardexProducto::where('motivo', 'produccion')->where('referencia_id', $produccion->id)->first();

        $costo      = round($consumos->sum(fn ($m) => $m->cantidad * ($m->materia->costo_unitario ?? 0)), 2);
        $valorVenta = round($produccion->cantidad * $produccion->producto->precio_venta, 2);
        $rend       = $produccion->producto->receta->rendimiento ?? null;
        $tandas     = $rend ? round($produccion->cantidad / max(1, $rend), 2) : null;

        // Si la petición viene por AJAX (clic en "Ver detalle" desde el listado),
        // devolvemos solo el fragmento con el contenido, sin el layout completo,
        // para poder mostrarlo en un modal sin recargar la página.
        if (request()->ajax()) {
            return view('produccion._detalle', compact('produccion', 'consumos', 'entrada', 'costo', 'valorVenta', 'tandas'));
        }

        return view('produccion.show', compact('produccion', 'consumos', 'entrada', 'costo', 'valorVenta', 'tandas'));
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
    public function recetas(Request $request)
    {
        $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => 'nullable|in:con,sin,aviso',
        ]);

        $todos = Producto::where('estado', 'activo')
            ->with(['receta.detalles.materia.unidad', 'categoria'])
            ->orderBy('nombre')
            ->get();

        // Costo, ganancia y avisos de cada receta
        foreach ($todos as $p) {
            $p->costo_receta = $p->costoSegunReceta();
            $p->margen_receta = $p->costo_receta !== null && $p->precio_venta > 0
                ? round(($p->precio_venta - $p->costo_receta) / $p->precio_venta * 100, 1) : null;
            $avisos = [];
            if ($p->receta) {
                foreach ($p->receta->detalles as $d) {
                    if (!$d->materia) continue;
                    if ($d->materia->estado !== 'activo') $avisos[] = "\"{$d->materia->nombre}\" está inactivo";
                    if ((float) $d->materia->costo_unitario <= 0) $avisos[] = "\"{$d->materia->nombre}\" no tiene costo";
                }
                if ($p->margen_receta !== null && $p->margen_receta < 0) $avisos[] = 'cuesta más de lo que se vende';
            }
            $p->avisos_receta = $avisos;
        }

        $stats = [
            'total'       => $todos->count(),
            'con_receta'  => $todos->filter(fn($p) => $p->receta)->count(),
            'sin_receta'  => $todos->filter(fn($p) => !$p->receta)->count(),
            'con_aviso'   => $todos->filter(fn($p) => !empty($p->avisos_receta))->count(),
        ];

        $productos = $todos
            ->when($request->filled('buscar'), fn ($c) => $c->filter(fn ($p) => mb_stripos($p->nombre, $request->buscar) !== false))
            ->when($request->estado === 'con', fn ($c) => $c->filter(fn ($p) => $p->receta))
            ->when($request->estado === 'sin', fn ($c) => $c->filter(fn ($p) => !$p->receta))
            ->when($request->estado === 'aviso', fn ($c) => $c->filter(fn ($p) => !empty($p->avisos_receta)))
            ->values();

        return view('produccion.recetas', compact('productos', 'stats'));
    }

    /** Pantalla para crear o editar la receta de un producto. */
    public function editarReceta(Producto $producto)
    {
        $producto->load(['categoria', 'receta.detalles.materia.unidad']);

        $materias = MateriaPrima::with('unidad')->orderBy('nombre')->get()
            ->filter(fn ($m) => $m->estado === 'activo' || $producto->receta?->detalles->contains('id_materia', $m->id))
            ->map(fn ($m) => [
                'id'          => $m->id,
                'nombre'      => $m->nombre . ($m->estado !== 'activo' ? ' (inactivo)' : ''),
                'abreviatura' => $m->unidad->abreviatura,
                'subunidad'   => self::subunidad($m->unidad->abreviatura),
                'costo'       => (float) $m->costo_unitario,
                'stock'       => (float) $m->stock_actual,
            ])->values();

        // Filas iniciales: lo que se escribió (si hubo un error) o la receta guardada
        $filas = old('ingredientes')
            ? collect(old('ingredientes'))->map(fn ($f) => ['id_materia' => $f['id_materia'] ?? '', 'cantidad' => $f['cantidad'] ?? '', 'unidad' => $f['unidad'] ?? 'base'])->values()
            : ($producto->receta?->detalles->map(fn ($d) => ['id_materia' => $d->id_materia, 'cantidad' => (float) $d->cantidad, 'unidad' => null])->values() ?? collect());

        $otras = Producto::where('id', '!=', $producto->id)->whereHas('receta')->orderBy('nombre')->get(['id', 'nombre']);

        return view('produccion.receta-editar', compact('producto', 'materias', 'filas', 'otras'));
    }

    public function crearReceta(Request $request)
    {
        $request->validate([
            'id_producto'       => 'required|exists:productos,id',
            'rendimiento'       => 'required|integer|min:1',
            'descripcion'       => 'nullable|string|max:500',
            'ingredientes'      => 'required|array|min:1',
            'ingredientes.*.id_materia' => 'required|distinct|exists:materia_prima,id',
            'ingredientes.*.cantidad'   => 'required|numeric|min:0.001',
            'ingredientes.*.unidad'     => ['nullable', Rule::in(['base', 'sub'])],
        ], [
            'id_producto.required'   => 'Selecciona un producto.',
            'rendimiento.required'   => 'El rendimiento es obligatorio.',
            'ingredientes.required'  => 'Agrega al menos un ingrediente.',
            'ingredientes.*.id_materia.distinct' => 'Hay un insumo repetido en la receta: júntalo en una sola fila.',
            'ingredientes.*.id_materia.required' => 'Elige el insumo de cada fila.',
            'ingredientes.*.cantidad.min'        => 'Cada cantidad debe ser mayor que cero.',
        ]);

        // Se escribe en g o mL si se prefiere: se guarda siempre en la unidad del insumo (kg o L)
        $materias = MateriaPrima::with('unidad')->whereIn('id', collect($request->ingredientes)->pluck('id_materia'))->get()->keyBy('id');
        $filas = [];
        foreach ($request->ingredientes as $ing) {
            $cant = (float) $ing['cantidad'];
            $abrev = $materias[$ing['id_materia']]->unidad->abreviatura ?? '';
            if (($ing['unidad'] ?? 'base') === 'sub' && self::subunidad($abrev)) $cant = $cant / 1000;
            $filas[] = ['id_materia' => $ing['id_materia'], 'cantidad' => round($cant, 3)];
        }
        foreach ($filas as $f) {
            if ($f['cantidad'] < 0.001) {
                return back()->withErrors(['ingredientes' => 'Una cantidad es demasiado pequeña (mínimo 1 g o 1 mL).'])->withInput();
            }
        }

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
            foreach ($filas as $f) {
                RecetaDetalle::create(['id_receta' => $receta->id] + $f);
            }

            DB::commit();
            return redirect()->route('produccion.recetas')
                ->with('success', 'Receta guardada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /** Unidad pequeña para escribir cantidades: kg → g, L → mL (null si no tiene). */
    public static function subunidad(?string $abrev): ?string
    {
        $u = strtolower(trim((string) $abrev));
        return match (true) {
            in_array($u, ['kg', 'kilo', 'kilos', 'kilogramo', 'kilogramos'], true) => 'g',
            in_array($u, ['l', 'lt', 'lts', 'litro', 'litros'], true) => 'mL',
            default => null,
        };
    }

    /** Lista legible de lo que falta, ej.: Harina (necesitas 4.3 kg, hay 2.5 kg) */
    private function faltantes($detalles, float $lotes): array
    {
        $faltan = [];
        foreach ($detalles as $d) {
            $necesario = round($d->cantidad * $lotes, 3);
            if ($d->materia->stock_actual + 1e-9 < $necesario) {
                $u = $d->materia->unidad->abreviatura ?? '';
                $faltan[] = "{$d->materia->nombre} (necesitas " . Cantidad::conUnidad($necesario, $u)
                    . ', hay ' . Cantidad::conUnidad($d->materia->stock_actual, $u) . ')';
            }
        }
        return $faltan;
    }

    // ─── API: obtener ingredientes de un producto (para JS) ──
    public function ingredientes(Producto $producto)
    {
        $producto->load('receta.detalles.materia.unidad');
        $receta = $producto->receta;
        if (!$receta) return response()->json(null);

        $max = PlanProduccion::maximo($producto);

        return response()->json([
            'rendimiento' => $receta->rendimiento,
            'descripcion' => $receta->descripcion,
            'maximo'      => $max['max'] ?? 0,
            'limita'      => $max['limita'] ?? null,
            'costo_unidad'=> $producto->costoSegunReceta(),
            'precio'      => (float) $producto->precio_venta,
            'stock'       => (int) $producto->stock_actual,
            'detalles'    => $receta->detalles->map(fn($d) => [
                'id_materia'   => $d->id_materia,
                'nombre'       => $d->materia->nombre,
                'cantidad'     => $d->cantidad,
                'abreviatura'  => $d->materia->unidad->abreviatura,
                'subunidad'    => self::subunidad($d->materia->unidad->abreviatura),
                'stock_actual' => $d->materia->stock_actual,
                'costo'        => (float) $d->materia->costo_unitario,
            ]),
        ]);
    }
}
