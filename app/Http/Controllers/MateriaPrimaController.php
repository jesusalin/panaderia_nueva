<?php
namespace App\Http\Controllers;
use App\Models\CompraDetalle;
use App\Models\MateriaPrima;
use App\Models\RecetaDetalle;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use App\Models\MovimientoInventario;
use App\Support\Cantidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MateriaPrimaController extends Controller
{
    public function index(Request $request) {
        $request->validate([
            'buscar'    => 'nullable|string|max:100',
            'proveedor' => ['nullable', 'regex:/^(\d+|ninguno)$/'],
            'estado'    => 'nullable|in:activo,inactivo',
            'stock'     => 'nullable|in:bajo,agotado,ok',
            'orden'     => 'nullable|in:nombre,urgente,dias,valor',
        ]);

        $query = MateriaPrima::with(['unidad', 'proveedor'])
            ->withCount(['recetaDetalles', 'compraDetalles', 'movimientos', 'ordenesAutomaticas'])
            ->when($request->buscar, fn($q) => $q->where('nombre', 'like', '%'.$request->buscar.'%'))
            ->when($request->proveedor === 'ninguno', fn($q) => $q->whereNull('id_proveedor'))
            ->when($request->proveedor && $request->proveedor !== 'ninguno', fn($q) => $q->where('id_proveedor', $request->proveedor))
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->when($request->stock === 'bajo', fn($q) => $q->where('stock_actual', '>', 0)->whereColumn('stock_actual', '<=', 'stock_minimo'))
            ->when($request->stock === 'agotado', fn($q) => $q->where('stock_actual', '<=', 0))
            ->when($request->stock === 'ok', fn($q) => $q->whereColumn('stock_actual', '>', 'stock_minimo'));

        match ($request->orden) {
            'urgente' => $query->orderByRaw('stock_actual * 1.0 / CASE WHEN stock_minimo = 0 THEN 1 ELSE stock_minimo END'),
            'valor'   => $query->orderByRaw('stock_actual * costo_unitario DESC'),
            default   => null,
        };

        // "Días que alcanza" se calcula con el consumo, por eso se ordena después de leer
        if ($request->orden === 'dias') {
            $todos = $query->orderBy('nombre')->get();
            $consumo = MateriaPrima::consumoDiario($todos->pluck('id'));
            $todos = $todos->sortBy(fn ($m) => $m->diasCobertura($consumo[$m->id] ?? null) ?? PHP_INT_MAX)->values();
            $materias = new \Illuminate\Pagination\LengthAwarePaginator(
                $todos->forPage((int) $request->input('page', 1), 12)->values(), $todos->count(), 12,
                (int) $request->input('page', 1), ['path' => $request->url(), 'query' => $request->query()]);
        } else {
            $materias = $query->orderBy('nombre')->paginate(12)->withQueryString();
            $consumo = MateriaPrima::consumoDiario($materias->pluck('id'));
        }

        $activos = MateriaPrima::where('estado', 'activo');
        $stats = [
            'total'    => MateriaPrima::count(),
            'activos'  => (clone $activos)->count(),
            'bajo'     => (clone $activos)->where('stock_actual', '>', 0)->whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
            'agotados' => (clone $activos)->where('stock_actual', '<=', 0)->count(),
            'valor'    => (float) (clone $activos)->sum(DB::raw('stock_actual * costo_unitario')),
        ];

        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();

        return view('materia_prima.index', compact('materias', 'proveedores', 'stats', 'consumo'));
    }

    /** Ficha del insumo: stock, para cuántos días alcanza, recetas, compras y movimientos. */
    public function show(MateriaPrima $materiaPrima) {
        $m = $materiaPrima->load(['unidad', 'proveedor']);

        $porDia = MateriaPrima::consumoDiario([$m->id])[$m->id] ?? null;
        $dias   = $m->diasCobertura($porDia);

        // Salidas por día (últimos 14 días, con ceros)
        $salidas = MovimientoInventario::where('id_materia', $m->id)->where('tipo', 'salida')
            ->where('created_at', '>=', now()->subDays(MateriaPrima::DIAS_CONSUMO - 1)->startOfDay())
            ->get(['cantidad', 'created_at'])
            ->groupBy(fn ($x) => $x->created_at->toDateString())->map->sum('cantidad');
        $consumoDias = collect(range(MateriaPrima::DIAS_CONSUMO - 1, 0))->map(fn ($n) => now()->subDays($n)->startOfDay())
            ->map(fn ($f) => ['fecha' => $f, 'cantidad' => (float) ($salidas[$f->toDateString()] ?? 0)]);

        $recetas = RecetaDetalle::with('receta.producto.categoria')->where('id_materia', $m->id)->get()
            ->filter(fn ($d) => $d->receta && $d->receta->producto)
            ->sortBy(fn ($d) => $d->receta->producto->nombre)->values();

        $compras = CompraDetalle::with('compra.proveedor')->where('id_materia', $m->id)
            ->whereHas('compra', fn ($c) => $c->where('estado', '!=', 'anulada'))
            ->latest('id')->limit(6)->get();

        $movimientos = MovimientoInventario::with('usuario:id,nombre')->where('id_materia', $m->id)
            ->orderByDesc('created_at')->orderByDesc('id')->limit(10)->get();

        $ultimoConteo = $m->conteosFisicos()->latest('id')->first();

        // Cuánto cobra cada proveedor por este insumo (del más barato al más caro)
        $preciosProv = MateriaPrima::preciosPorProveedor([$m->id])[$m->id] ?? collect();
        if ($m->id_proveedor && !$preciosProv->contains('id_proveedor', $m->id_proveedor) && $m->proveedor) {
            $preciosProv->push(['id_proveedor' => $m->id_proveedor, 'nombre' => $m->proveedor->nombre, 'activo' => $m->proveedor->estado === 'activo',
                'precio' => null, 'fecha' => null, 'veces' => 0, 'promedio' => null]);
        }

        return view('materia_prima.show', compact('m', 'porDia', 'dias', 'consumoDias', 'recetas', 'compras', 'movimientos', 'ultimoConteo', 'preciosProv'));
    }

    public function create() {
        $unidades    = UnidadMedida::orderBy('nombre')->get();
        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();
        $nombres     = MateriaPrima::orderBy('nombre')->pluck('nombre');
        return view('materia_prima.create', compact('unidades', 'proveedores', 'nombres'));
    }

    public function store(Request $request) {
        $datos = $request->validate($this->reglas() + ['stock_actual' => 'required|numeric|min:0'], $this->mensajes());
        $datos['es_control'] = $request->boolean('es_control');

        DB::transaction(function () use ($datos) {
            $m = MateriaPrima::create($datos);
            // El stock con el que nace el insumo queda registrado como movimiento
            if ($m->stock_actual > 0) {
                MovimientoInventario::create([
                    'id_materia' => $m->id, 'id_usuario' => auth()->id(),
                    'tipo' => 'entrada', 'motivo' => 'ajuste_manual', 'referencia_id' => null,
                    'cantidad' => $m->stock_actual, 'stock_antes' => 0, 'stock_despues' => $m->stock_actual,
                    'observacion' => 'Stock inicial al registrar el insumo', 'created_at' => now(),
                ]);
            }
        });
        return redirect()->route('materia-prima.index')->with('success', 'Materia prima creada.');
    }

    public function edit(MateriaPrima $materiaPrima) {
        $unidades    = UnidadMedida::orderBy('nombre')->get();
        $proveedores = Proveedor::where('estado', 'activo')->orWhere('id', $materiaPrima->id_proveedor)->orderBy('nombre')->get();
        $usos = [
            'recetas' => $materiaPrima->recetaDetalles()->count(),
            'compras' => $materiaPrima->compraDetalles()->count(),
        ];
        $usos['movimientos'] = $materiaPrima->movimientos()->count();
        $nombres = MateriaPrima::where('id', '<>', $materiaPrima->id)->orderBy('nombre')->pluck('nombre');
        // consumo diario (últimos 14 días) para sugerir el stock mínimo
        $porDia = MateriaPrima::consumoDiario([$materiaPrima->id])[$materiaPrima->id] ?? null;
        return view('materia_prima.edit', compact('materiaPrima', 'unidades', 'proveedores', 'usos', 'nombres', 'porDia'));
    }

    public function update(Request $request, MateriaPrima $materiaPrima) {
        $datos = $request->validate($this->reglas($materiaPrima->id), $this->mensajes());
        $datos['es_control'] = $request->boolean('es_control');
        // El stock NO se cambia aquí (rompería el kardex): se usa "Ajustar stock" o el conteo físico
        // El costo se muestra con 2 decimales: si no lo cambiaron, se conserva el promedio exacto
        if (abs((float) $datos['costo_unitario'] - round((float) $materiaPrima->costo_unitario, 2)) < 0.005) {
            unset($datos['costo_unitario']);
        }
        $materiaPrima->update($datos);
        return redirect()->route('materia-prima.show', $materiaPrima)->with('success', 'Materia prima actualizada.');
    }

    private function reglas(?int $ignorar = null): array {
        return [
            'nombre'              => ['required', 'string', 'max:150', \Illuminate\Validation\Rule::unique('materia_prima', 'nombre')->ignore($ignorar)],
            'id_unidad'           => 'required|exists:unidades_medida,id',
            'id_proveedor'        => 'nullable|exists:proveedores,id',
            'stock_minimo'        => 'required|numeric|min:0',
            'cantidad_reposicion' => 'nullable|numeric|min:0',
            'costo_unitario'      => 'required|numeric|min:0',
            'estado'              => 'required|in:activo,inactivo',
        ];
    }

    private function mensajes(): array {
        return [
            'nombre.unique'          => 'Ya existe un insumo con ese nombre.',
            'costo_unitario.required'=> 'Escribe cuánto cuesta.',
            'stock_minimo.required'  => 'Escribe el stock mínimo (puede ser 0).',
        ];
    }

    public function toggleEstado(MateriaPrima $materiaPrima) {
        $nuevoEstado = $materiaPrima->estado === 'activo' ? 'inactivo' : 'activo';
        $materiaPrima->update(['estado' => $nuevoEstado]);

        return back()->with('success', 'Materia prima ' . ($nuevoEstado === 'activo' ? 'activada' : 'desactivada') . '.');
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

    /** Formulario de ajuste: merma (se perdió) o corrección por conteo (hay otra cantidad). */
    public function ajusteForm(MateriaPrima $materiaPrima) {
        $materiaPrima->load('unidad');
        return view('materia_prima.ajuste', compact('materiaPrima'));
    }

    /** Motivos rápidos de merma. */
    public const MOTIVOS_MERMA = [
        'vencido'   => 'Se venció',
        'roto'      => 'Se rompió o derramó',
        'malestado' => 'En mal estado (humedad, plagas, frío)',
        'otro'      => 'Otro motivo',
    ];

    public function ajusteStore(Request $request, MateriaPrima $materiaPrima) {
        $request->validate([
            'tipo_ajuste'  => 'required|in:merma,conteo',
            'unidad'       => 'nullable|in:base,sub',
            'cantidad'     => 'required_if:tipo_ajuste,merma|nullable|numeric|gt:0',
            'stock_real'   => 'required_if:tipo_ajuste,conteo|nullable|numeric|min:0',
            'motivo_merma' => ['required_if:tipo_ajuste,merma', 'nullable', Rule::in(array_keys(self::MOTIVOS_MERMA))],
            'observacion'  => 'required_if:motivo_merma,otro|required_if:tipo_ajuste,conteo|nullable|string|max:200',
        ], [
            'cantidad.required_if'    => 'Indica cuánto se perdió.',
            'stock_real.required_if'  => 'Indica cuánto contaste.',
            'observacion.required_if' => 'Escribe el motivo.',
        ]);

        // Se puede escribir en g/mL: se guarda en la unidad del insumo (kg/L)
        $unidad = $materiaPrima->unidad->abreviatura ?? '';
        $factor = ($request->unidad === 'sub' && ProduccionController::subunidad($unidad)) ? 1000 : 1;

        $resultado = DB::transaction(function () use ($request, $materiaPrima, $factor) {
            $m = MateriaPrima::lockForUpdate()->findOrFail($materiaPrima->id);
            $antes = (float) $m->stock_actual;
            $merma = $request->tipo_ajuste === 'merma';
            $despues = round($merma ? $antes - $request->cantidad / $factor : $request->stock_real / $factor, 3);

            if ($despues < 0) return ['error' => 'No puedes descontar más de lo que hay (' . Cantidad::conUnidad($antes, $m->unidad->abreviatura ?? '') . ').'];
            if (abs($despues - $antes) < 0.0005) return ['error' => 'Lo contado es igual al stock del sistema: no hay nada que ajustar.'];

            $texto = $merma
                ? 'Merma: ' . self::MOTIVOS_MERMA[$request->motivo_merma] . ($request->observacion ? ' — ' . $request->observacion : '')
                : 'Corrección por conteo: ' . $request->observacion;

            $m->update(['stock_actual' => $despues]);
            MovimientoInventario::create([
                'id_materia'    => $m->id,
                'id_usuario'    => auth()->id(),
                'tipo'          => $merma ? 'salida' : 'ajuste',
                'motivo'        => $merma ? 'merma' : 'ajuste_manual',
                'referencia_id' => null,
                'cantidad'      => abs(round($despues - $antes, 3)),
                'stock_antes'   => $antes,
                'stock_despues' => $despues,
                'observacion'   => mb_substr($texto, 0, 255),
                'created_at'    => now(),
            ]);
            return ['antes' => $antes, 'despues' => $despues, 'u' => $m->unidad->abreviatura ?? ''];
        });

        if (isset($resultado['error'])) {
            return back()->withInput()->with('error', $resultado['error']);
        }
        return redirect()->route('materia-prima.show', $materiaPrima)->with('success',
            "Stock de \"{$materiaPrima->nombre}\" ajustado: " . Cantidad::conUnidad($resultado['antes'], $resultado['u'])
            . ' → ' . Cantidad::conUnidad($resultado['despues'], $resultado['u']) . ' (quedó en el kardex).');
    }
}
