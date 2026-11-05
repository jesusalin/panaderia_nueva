<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\OrdenAutomatica;
use App\Models\Proveedor;
use App\Support\Cantidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ComprasController extends Controller
{
    public const PERIODOS = ['hoy' => 'Hoy', '7' => '7 días', 'mes' => 'Este mes', '30' => '30 días', 'todo' => 'Todo'];
    public const ESTADOS  = ['pendiente' => 'Por recibir', 'atrasada' => 'Atrasadas', 'recibida' => 'Recibidas', 'anulada' => 'Anuladas'];

    // ─────────────────────────────── LISTADO ───────────────────────────────
    public function index(Request $request)
    {
        $request->validate([
            'buscar'       => 'nullable|string|max:100',
            'id_proveedor' => 'nullable|integer',
            'estado'       => ['nullable', Rule::in(array_keys(self::ESTADOS))],
            'periodo'      => ['nullable', Rule::in(array_keys(self::PERIODOS))],
            'desde'        => 'nullable|date',
            'hasta'        => 'nullable|date',
        ]);
        // compatibilidad con los enlaces anteriores
        $desde = $request->input('desde', $request->input('fecha_desde'));
        $hasta = $request->input('hasta', $request->input('fecha_hasta'));
        $periodo = $request->input('periodo', ($desde || $hasta) ? null : 'todo');

        $query = Compra::with(['proveedor', 'usuario', 'detalles.materia.unidad'])->withCount('detalles');

        if ($request->filled('buscar')) {
            $b = trim($request->buscar);
            $id = ltrim(preg_replace('/\D/', '', $b), '0');
            $query->where(function ($q) use ($b, $id) {
                $q->where('numero_doc', 'like', "%{$b}%")
                  ->orWhereHas('proveedor', fn ($p) => $p->where('nombre', 'like', "%{$b}%")->orWhere('ruc', 'like', "%{$b}%"))
                  ->orWhereHas('detalles.materia', fn ($m) => $m->where('nombre', 'like', "%{$b}%"));
                if ($id !== '' && str_starts_with($b, '#')) $q->orWhere('compras.id', (int) $id);
            });
        }
        if ($request->filled('id_proveedor')) $query->where('id_proveedor', $request->id_proveedor);

        $estado = $request->estado;
        if ($estado === 'atrasada') {
            $query->where('estado', 'pendiente')->whereDate('fecha_compra', '<', now()->subDays(Compra::DIAS_ATRASO)->toDateString());
        } elseif ($estado) {
            $query->where('estado', $estado);
        }

        match ($periodo) {
            'hoy'   => $query->whereDate('fecha_compra', today()),
            '7'     => $query->whereDate('fecha_compra', '>=', today()->subDays(6)),
            '30'    => $query->whereDate('fecha_compra', '>=', today()->subDays(29)),
            'mes'   => $query->whereDate('fecha_compra', '>=', today()->startOfMonth()),
            default => null,
        };
        if (!$periodo) {
            if ($desde) $query->whereDate('fecha_compra', '>=', $desde);
            if ($hasta) $query->whereDate('fecha_compra', '<=', $hasta);
        }

        // Resumen general (no depende de los filtros)
        $mes = fn ($c) => $c->where('estado', '!=', 'anulada');
        $stats = [
            'pendientes'       => Compra::where('estado', 'pendiente')->count(),
            'monto_pendiente'  => (float) Compra::where('estado', 'pendiente')->sum('total'),
            'atrasadas'        => Compra::where('estado', 'pendiente')->whereDate('fecha_compra', '<', now()->subDays(Compra::DIAS_ATRASO)->toDateString())->count(),
            'gasto_mes'        => (float) $mes(Compra::query())->whereDate('fecha_compra', '>=', today()->startOfMonth())->sum('total'),
            // mismo tramo del mes anterior (del 1 al mismo día), para comparar parejo
            'gasto_mes_ant'    => (float) $mes(Compra::query())->whereBetween('fecha_compra', [today()->subMonthNoOverflow()->startOfMonth()->toDateString(), today()->subMonthNoOverflow()->toDateString()])->sum('total'),
            'compras_mes'      => $mes(Compra::query())->whereDate('fecha_compra', '>=', today()->startOfMonth())->count(),
        ];
        // Lo filtrado
        $filtrado = ['n' => (clone $query)->count(), 'total' => (float) (clone $query)->where('estado', '!=', 'anulada')->sum('total')];

        $compras = $query->orderByDesc('fecha_compra')->orderByDesc('id')->paginate(15)->withQueryString();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('compras.index', compact('compras', 'proveedores', 'stats', 'filtrado', 'periodo', 'desde', 'hasta'));
    }

    // ─────────────────────────────── NUEVA ───────────────────────────────
    public function create(Request $request)
    {
        $proveedores = Proveedor::where('estado', 'activo')->withCount('insumos')->orderBy('nombre')->get();
        $materias    = MateriaPrima::where('estado', 'activo')->with('unidad')->orderBy('nombre')->get();

        // Último precio de cada proveedor por insumo: al elegir proveedor se propone su precio
        $precios = MateriaPrima::preciosPorProveedor($materias->pluck('id'));

        // Datos para el formulario (se usan en el buscador de insumos y en los botones rápidos)
        $datosInsumos = $materias->map(fn ($m) => [
            'pp'    => ($precios[$m->id] ?? collect())->mapWithKeys(fn ($x) => [$x['id_proveedor'] => $x['precio']]),
            'id'    => $m->id,
            'n'     => $m->nombre,
            'u'     => $m->unidad->abreviatura ?? '',
            'sub'   => ProduccionController::subunidad($m->unidad->abreviatura ?? ''),
            'stock' => (float) $m->stock_actual,
            'min'   => (float) $m->stock_minimo,
            'costo' => (float) $m->costo_unitario,
            'prov'  => $m->id_proveedor,
            'repo'  => (float) ($m->cantidad_reposicion ?: max($m->stock_minimo * 2 - $m->stock_actual, 0)),
            'bajo'  => $m->tieneStockBajo(),
        ])->values();

        // Última compra (no anulada) a cada proveedor, para "Repetir la última compra"
        $ultimas = Compra::where('estado', '!=', 'anulada')->with('detalles')
            ->whereIn('id', Compra::where('estado', '!=', 'anulada')->selectRaw('MAX(id)')->groupBy('id_proveedor'))
            ->get()->mapWithKeys(fn ($c) => [$c->id_proveedor => [
                'id' => $c->id, 'fecha' => $c->fecha_compra->format('d/m/Y'), 'igv' => (bool) $c->incluye_igv,
                'items' => $c->detalles->map(fn ($d) => ['id' => $d->id_materia, 'cant' => $d->cantidad, 'precio' => $d->precio_unitario])->values(),
            ]]);

        $datosProveedores = $proveedores->mapWithKeys(fn ($p) => [$p->id => [
            'nombre' => $p->nombre, 'tel' => $p->telefono, 'contacto' => $p->contacto, 'insumos' => $p->insumos_count,
        ]]);

        // Viene desde una ficha de insumo, reporte de stock o "Repetir": se rellena solo
        $inicial = ['proveedor' => $request->integer('proveedor') ?: null, 'items' => [], 'igv' => false];
        if ($request->filled('materia') && ($m = $materias->firstWhere('id', $request->integer('materia')))) {
            $inicial['proveedor'] ??= $m->id_proveedor;
            $deEse = ($precios[$m->id] ?? collect())->firstWhere('id_proveedor', $inicial['proveedor']);
            $inicial['items'][] = ['id' => $m->id, 'cant' => $m->cantidad_reposicion ?: max($m->stock_minimo * 2 - $m->stock_actual, 1), 'precio' => $deEse['precio'] ?? (float) $m->costo_unitario];
        }
        if ($request->filled('repetir') && ($c = Compra::with('detalles')->find($request->integer('repetir')))) {
            $inicial['proveedor'] = $c->id_proveedor;
            $inicial['igv'] = (bool) $c->incluye_igv;
            $inicial['items'] = $c->detalles->map(fn ($d) => ['id' => $d->id_materia, 'cant' => $d->cantidad, 'precio' => $d->precio_unitario])->values()->all();
        }

        return view('compras.create', compact('proveedores', 'materias', 'datosInsumos', 'ultimas', 'datosProveedores', 'inicial'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_proveedor'  => ['required', Rule::exists('proveedores', 'id')->where('estado', 'activo')],
            'fecha_compra'  => 'required|date|before_or_equal:today',
            'numero_doc'    => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:500',
            'incluye_igv'   => 'nullable|boolean',
            'recibir_ahora' => 'nullable|boolean',
            'materias'      => 'required|array|min:1',
            'materias.*.id_materia'      => 'required|distinct|exists:materia_prima,id',
            'materias.*.cantidad'        => 'required|numeric|min:0.001',
            'materias.*.unidad'          => 'nullable|in:base,sub',
            'materias.*.precio_unitario' => 'required|numeric|min:0',
        ], [
            'id_proveedor.exists'             => 'Elige un proveedor activo.',
            'fecha_compra.before_or_equal'    => 'La fecha no puede ser futura.',
            'materias.required'               => 'Agrega al menos un insumo.',
            'materias.*.id_materia.distinct'  => 'Un insumo está repetido: junta las cantidades en una sola fila.',
            'materias.*.cantidad.min'         => 'Cada cantidad debe ser mayor que 0.',
        ]);

        // La misma factura no se puede registrar dos veces al mismo proveedor
        $doc = trim((string) $request->numero_doc);
        if ($doc !== '' && Compra::where('id_proveedor', $request->id_proveedor)->where('numero_doc', $doc)->where('estado', '!=', 'anulada')->exists()) {
            throw ValidationException::withMessages(['numero_doc' => "La factura {$doc} de este proveedor ya está registrada."]);
        }

        $incluyeIgv = $request->boolean('incluye_igv');
        $compra = DB::transaction(function () use ($request, $incluyeIgv, $doc) {
            $detalles = [];
            $suma = 0;
            foreach ($request->materias as $item) {
                $cantidad = round((float) $item['cantidad'] / (($item['unidad'] ?? 'base') === 'sub' ? 1000 : 1), 3);
                $precio   = round((float) $item['precio_unitario'], 2);
                $sub      = round($cantidad * $precio, 2);
                $suma    += $sub;
                $detalles[] = ['id_materia' => $item['id_materia'], 'cantidad' => $cantidad, 'precio_unitario' => $precio, 'subtotal' => $sub];
            }

            $compra = Compra::create([
                'id_proveedor'  => $request->id_proveedor,
                'id_usuario'    => auth()->id(),
                'numero_doc'    => $doc !== '' ? $doc : null,
                'fecha_compra'  => $request->fecha_compra,
                'incluye_igv'   => $incluyeIgv,
                'estado'        => 'pendiente',
                'observaciones' => $request->observaciones,
            ] + Compra::totales($suma, $incluyeIgv));

            foreach ($detalles as $d) $compra->detalles()->create($d);

            if ($request->boolean('recibir_ahora')) {
                $this->recibirDetalles($compra->load('detalles'), [], [], 'Recibida al registrarla');
            }
            return $compra;
        });

        return redirect()->route('compras.show', $compra)->with('success', $request->boolean('recibir_ahora')
            ? "Compra {$compra->codigo} registrada y recibida: el stock ya subió."
            : "Compra {$compra->codigo} registrada. Recíbela cuando llegue el pedido.");
    }

    // ─────────────────────────────── DETALLE ───────────────────────────────
    public function show(Compra $compra)
    {
        $compra->load(['proveedor', 'usuario', 'usuarioRecibe', 'usuarioAnula', 'detalles.materia.unidad', 'orden']);

        // Si la petición viene por AJAX (modal antiguo), devolvemos solo el fragmento
        if (request()->ajax()) {
            return view('compras._detalle', compact('compra'));
        }

        // Precio anterior de cada insumo (la compra válida anterior a esta) para ver si subió o bajó
        $anteriores = [];
        foreach ($compra->detalles as $d) {
            $previo = CompraDetalle::where('id_materia', $d->id_materia)->where('id_compra', '<', $compra->id)
                ->whereHas('compra', fn ($q) => $q->where('estado', '!=', 'anulada'))
                ->with('compra:id,incluye_igv')->orderByDesc('id_compra')->first();
            if ($previo) {
                $anteriores[$d->id_materia] = $this->precioSinIgv($previo->precio_unitario, (bool) $previo->compra->incluye_igv);
            }
        }

        return view('compras.show', compact('compra', 'anteriores'));
    }

    // ─────────────────────────────── RECEPCIÓN ───────────────────────────────
    public function recibirForm(Compra $compra)
    {
        if ($compra->estado !== 'pendiente') {
            return redirect()->route('compras.show', $compra)->withErrors(['error' => 'Esta compra ya fue ' . $compra->estado . '.']);
        }
        $compra->load(['proveedor', 'detalles.materia.unidad']);
        return view('compras.recibir', compact('compra'));
    }

    public function recibir(Request $request, Compra $compra)
    {
        if ($compra->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta compra ya fue procesada.']);
        }
        $request->validate([
            'recibido'          => 'nullable|array',
            'recibido.*'        => 'nullable|numeric|min:0',
            'unidad'            => 'nullable|array',
            'unidad.*'          => 'nullable|in:base,sub',
            'precio'            => 'nullable|array',
            'precio.*'          => 'nullable|numeric|min:0',
            'nota_recepcion'    => 'nullable|string|max:200',
        ]);

        // Cantidades en la unidad del insumo (g → kg, mL → L)
        $recibido = [];
        foreach ((array) $request->input('recibido', []) as $id => $v) {
            if ($v === null || $v === '') continue;
            $recibido[(int) $id] = round((float) $v / ((($request->input('unidad')[$id] ?? 'base') === 'sub') ? 1000 : 1), 3);
        }
        if ($recibido && array_sum($recibido) <= 0) {
            throw ValidationException::withMessages(['recibido' => 'No llegó nada: si el pedido no va a llegar, anula la compra.']);
        }
        $hayDiferencia = collect($compra->detalles)->contains(fn ($d) => isset($recibido[$d->id]) && abs($recibido[$d->id] - $d->cantidad) > 0.0005);
        if ($hayDiferencia && !trim((string) $request->nota_recepcion)) {
            throw ValidationException::withMessages(['nota_recepcion' => 'Llegó una cantidad distinta a la pedida: escribe qué pasó.']);
        }

        DB::transaction(fn () => $this->recibirDetalles($compra, $recibido, array_map('floatval', array_filter((array) $request->input('precio', []), fn ($p) => $p !== null && $p !== '')), $request->nota_recepcion));

        return redirect()->route('compras.show', $compra)->with('success', "Compra {$compra->codigo} recibida: el stock de los insumos ya subió."
            . ($hayDiferencia ? ' Se registró lo que llegó de verdad.' : ''));
    }

    /**
     * Entra al stock lo que llegó y deja el movimiento en el kardex.
     * $recibido y $precios van por id de detalle; si faltan se toma lo pedido y el precio de la compra.
     */
    private function recibirDetalles(Compra $compra, array $recibido, array $precios, ?string $nota): void
    {
        $suma = 0;
        foreach ($compra->detalles as $d) {
            $cant   = $recibido[$d->id] ?? (float) $d->cantidad;
            $precio = round($precios[$d->id] ?? (float) $d->precio_unitario, 2);
            $materia = MateriaPrima::lockForUpdate()->findOrFail($d->id_materia);

            if ($cant > 0) {
                $antes   = (float) $materia->stock_actual;
                $despues = round($antes + $cant, 3);
                $materia->update([
                    'stock_actual'   => $despues,
                    // costo promedio ponderado (sin IGV): lo que había a su costo + lo que entra a su precio
                    'costo_unitario' => MateriaPrima::costoPromedio($antes, (float) $materia->costo_unitario, $cant, $this->precioSinIgv($precio, (bool) $compra->incluye_igv)),
                ]);
                $u = $materia->unidad->abreviatura ?? '';
                MovimientoInventario::create([
                    'id_materia'    => $materia->id,
                    'id_usuario'    => auth()->id(),
                    'tipo'          => 'entrada',
                    'motivo'        => 'compra',
                    'referencia_id' => $compra->id,
                    'cantidad'      => $cant,
                    'stock_antes'   => $antes,
                    'stock_despues' => $despues,
                    'observacion'   => "Compra {$compra->codigo}" . ($compra->proveedor ? " — {$compra->proveedor->nombre}" : '')
                        . (abs($cant - $d->cantidad) > 0.0005 ? ' (llegó ' . Cantidad::conUnidad($cant, $u) . ' de ' . Cantidad::conUnidad($d->cantidad, $u) . ')' : ''),
                    'created_at'    => now(),
                ]);
            }

            $sub = round($cant * $precio, 2);
            $suma += $sub;
            $d->update(['cantidad_recibida' => $cant, 'precio_unitario' => $precio, 'subtotal' => $sub]);
        }

        $compra->update([
            'estado'            => 'recibida',
            'recibida_at'       => now(),
            'id_usuario_recibe' => auth()->id(),
            'nota_recepcion'    => $nota ? mb_substr($nota, 0, 200) : null,
        ] + Compra::totales($suma, (bool) $compra->incluye_igv));
    }

    private function precioSinIgv(float $precio, bool $incluyeIgv): float
    {
        return $incluyeIgv ? round($precio / (1 + Compra::IGV), 2) : $precio;
    }

    // ─────────────────────────────── ANULAR ───────────────────────────────
    public function anular(Request $request, Compra $compra)
    {
        if ($compra->estado === 'anulada') {
            return back()->withErrors(['error' => 'Esta compra ya está anulada.']);
        }
        $request->validate(['motivo_anulacion' => 'required|string|min:4|max:200'], [
            'motivo_anulacion.required' => 'Escribe por qué anulas la compra.',
            'motivo_anulacion.min'      => 'Escribe por qué anulas la compra.',
        ]);

        $estadoOriginal = $compra->estado;
        $compra->load('detalles.materia.unidad');

        // Si ya se había recibido, hay que revertir el stock. Primero se valida TODO.
        if ($estadoOriginal === 'recibida') {
            foreach ($compra->detalles as $d) {
                $m = $d->materia;
                if ($m && $m->stock_actual + 0.0005 < $d->cantidad_real) {
                    $u = $m->unidad->abreviatura ?? '';
                    return back()->withErrors(['error' => "No se puede anular: ya se usó parte de \"{$m->nombre}\" que llegó con esta compra. "
                        . 'Hay ' . Cantidad::conUnidad($m->stock_actual, $u) . ' y habría que devolver ' . Cantidad::conUnidad($d->cantidad_real, $u) . ' (el stock quedaría negativo).']);
                }
            }
        }

        $ordenVuelve = false;
        DB::transaction(function () use ($compra, $estadoOriginal, $request, &$ordenVuelve) {
            if ($estadoOriginal === 'recibida') {
                foreach ($compra->detalles as $d) {
                    if ($d->cantidad_real <= 0) continue;
                    $m = MateriaPrima::lockForUpdate()->find($d->id_materia);
                    if (!$m) continue;
                    $antes = (float) $m->stock_actual;
                    $despues = round($antes - $d->cantidad_real, 3);
                    $m->update([
                        'stock_actual'   => $despues,
                        // se saca del promedio lo que había entrado con esta compra
                        'costo_unitario' => MateriaPrima::costoSinEntrada($antes, (float) $m->costo_unitario, $d->cantidad_real, $this->precioSinIgv($d->precio_unitario, (bool) $compra->incluye_igv)),
                    ]);
                    MovimientoInventario::create([
                        'id_materia'    => $m->id,
                        'id_usuario'    => auth()->id(),
                        'tipo'          => 'salida',
                        'motivo'        => 'devolucion',
                        'referencia_id' => $compra->id,
                        'cantidad'      => $d->cantidad_real,
                        'stock_antes'   => $antes,
                        'stock_despues' => $despues,
                        'observacion'   => "Anulación de la compra {$compra->codigo}: " . $request->motivo_anulacion,
                        'created_at'    => now(),
                    ]);
                }
            }

            $compra->update([
                'estado'           => 'anulada',
                'anulada_at'       => now(),
                'id_usuario_anula' => auth()->id(),
                'motivo_anulacion' => $request->motivo_anulacion,
            ]);

            // Si venía de una orden automática y el insumo sigue faltando, la orden vuelve a pendientes
            $orden = OrdenAutomatica::where('id_compra', $compra->id)->first();
            if ($orden && !OrdenAutomatica::where('id_materia', $orden->id_materia)->where('estado', 'pendiente')->exists()) {
                $orden->update(['estado' => 'pendiente', 'id_compra' => null]);
                $ordenVuelve = true;
            }
        });

        $mensaje = "Compra {$compra->codigo} anulada.";
        if ($estadoOriginal === 'recibida') $mensaje .= ' El stock de los insumos se devolvió (quedó en el kardex).';
        if ($ordenVuelve) $mensaje .= ' Su orden automática volvió a pendientes.';
        return redirect()->route('compras.show', $compra)->with('success', $mensaje);
    }
}
