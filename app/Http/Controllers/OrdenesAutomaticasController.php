<?php
namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\MateriaPrima;
use App\Models\OrdenAutomatica;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrdenesAutomaticasController extends Controller
{
    /**
     * Revisa los insumos activos y crea una orden pendiente para los que estén
     * en su stock mínimo o por debajo y no tengan ya una orden abierta.
     * Devuelve cuántas creó.
     */
    public static function revisar(): int
    {
        // No se crea otra orden si el insumo ya tiene una abierta o ya viene en una compra por recibir
        $abiertas = OrdenAutomatica::where('estado', 'pendiente')->pluck('id_materia');
        $enCamino = \App\Models\CompraDetalle::whereHas('compra', fn ($q) => $q->where('estado', 'pendiente'))->pluck('id_materia');
        $bajos = MateriaPrima::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->whereNotIn('id', $abiertas->merge($enCamino)->unique()->all())->get();

        // Si la descartaron, no se vuelve a sugerir mientras el stock no siga bajando
        $descartes = OrdenAutomatica::where('estado', 'descartada')->whereIn('id_materia', $bajos->pluck('id'))
            ->orderByDesc('id')->get()->unique('id_materia')->keyBy('id_materia');
        $bajos = $bajos->reject(fn ($m) => isset($descartes[$m->id]) && $m->stock_actual >= $descartes[$m->id]->stock_al_generar - 0.0005);

        foreach ($bajos as $m) {
            OrdenAutomatica::create([
                'id_materia'        => $m->id,
                'id_proveedor'      => $m->id_proveedor,
                'stock_al_generar'  => $m->stock_actual,
                'stock_minimo'      => $m->stock_minimo,
                // La configurada en el insumo o, si no hay, lo que falta para llegar al doble del mínimo
                'cantidad_sugerida' => $m->cantidad_reposicion ?: max(round($m->stock_minimo * 2 - $m->stock_actual, 3), $m->stock_minimo),
                'estado'            => 'pendiente',
            ]);
        }
        return $bajos->count();
    }

    public function generar()
    {
        $creadas = self::revisar();
        return redirect()->route('ordenes-automaticas.index')
            ->with('success', $creadas > 0
                ? "Se generaron {$creadas} nueva(s) orden(es) de reposición."
                : 'Todo al día: no hay insumos con stock bajo sin orden.');
    }

    public function index(Request $request)
    {
        // Es automático: al entrar se revisa el stock y se crean las órdenes que falten
        $nuevas = self::revisar();
        $estado = $request->input('estado', 'pendiente');

        $conteos = [
            'pendiente'  => OrdenAutomatica::where('estado', 'pendiente')->count(),
            'convertida' => OrdenAutomatica::where('estado', 'convertida')->count(),
            'descartada' => OrdenAutomatica::where('estado', 'descartada')->count(),
        ];

        if ($estado === 'pendiente') {
            $pendientes = OrdenAutomatica::with(['materia.unidad', 'materia.proveedor', 'proveedor'])->where('estado', 'pendiente')->orderBy('created_at')->get();
            // Si al insumo le pusieron proveedor después de crear la orden, se usa el actual
            $pendientes->each(function ($o) {
                if (!$o->id_proveedor && $o->materia?->id_proveedor) {
                    $o->update(['id_proveedor' => $o->materia->id_proveedor]);
                    $o->setRelation('proveedor', $o->materia->proveedor);
                }
            });
            $consumo = MateriaPrima::consumoDiario($pendientes->pluck('id_materia'));
            // Agrupadas por proveedor; las que no tienen proveedor van al final
            $grupos = $pendientes->groupBy(fn ($o) => $o->id_proveedor ?: 0)
                ->sortBy(fn ($g, $id) => $id ? $g->first()->proveedor->nombre : "\u{10FFFF}");
            // Para elegir a quién pedir cada insumo: lo que cobra cada proveedor y la lista de activos
            $precios = MateriaPrima::preciosPorProveedor($pendientes->pluck('id_materia'));
            $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'telefono']);
            return view('ordenes-automaticas.index', compact('conteos', 'estado', 'nuevas', 'grupos', 'consumo', 'precios', 'proveedores'));
        }

        $ordenes = OrdenAutomatica::with(['materia.unidad', 'proveedor', 'compra'])
            ->where('estado', $estado === 'todas' ? '!=' : '=', $estado === 'todas' ? '' : $estado)
            ->orderByDesc('updated_at')->paginate(15)->withQueryString();

        return view('ordenes-automaticas.index', compact('conteos', 'estado', 'nuevas', 'ordenes'));
    }

    /** Una sola orden → una compra pendiente (con la cantidad que se haya escrito) */
    public function convertir(Request $request, OrdenAutomatica $ordenAutomatica)
    {
        return $this->convertirVarias($request->merge([
            'ordenes'    => [$ordenAutomatica->id],
            'cantidades' => $request->has('cantidad') ? [$ordenAutomatica->id => $request->input('cantidad')] : $request->input('cantidades', []),
        ]));
    }

    /**
     * Varias órdenes → una compra por proveedor.
     * Si se pidieron órdenes de 3 proveedores, salen 3 compras pendientes.
     */
    public function convertirVarias(Request $request)
    {
        $request->validate([
            'ordenes'      => 'required|array|min:1',
            'ordenes.*'    => 'integer',
            'cantidades'   => 'nullable|array',
            'cantidades.*' => 'nullable|numeric|min:0.001',
            'proveedores'  => 'nullable|array',
            'proveedores.*'=> 'nullable|integer',
        ], ['ordenes.required' => 'Marca al menos una orden.', 'cantidades.*.min' => 'Cada cantidad debe ser mayor que 0.']);

        $ordenes = OrdenAutomatica::with(['materia', 'proveedor'])->whereIn('id', $request->ordenes)->get();
        if ($ordenes->isEmpty() || $ordenes->contains(fn ($o) => $o->estado !== 'pendiente')) {
            return back()->withErrors(['error' => 'Alguna de las órdenes ya fue procesada. Revisa la lista.']);
        }

        // Si eligieron otro proveedor para alguna orden, se usa ese (aunque no sea el principal del insumo)
        $elegidos = array_filter((array) $request->input('proveedores', []));
        if ($elegidos) {
            $activos = Proveedor::where('estado', 'activo')->whereIn('id', $elegidos)->get()->keyBy('id');
            foreach ($ordenes as $o) {
                $idElegido = (int) ($elegidos[$o->id] ?? 0);
                if ($idElegido && isset($activos[$idElegido]) && $idElegido !== (int) $o->id_proveedor) {
                    $o->id_proveedor = $idElegido;
                    $o->setRelation('proveedor', $activos[$idElegido]);
                }
            }
        }
        $precios = MateriaPrima::preciosPorProveedor($ordenes->pluck('id_materia'));
        $sinProveedor = $ordenes->filter(fn ($o) => !$o->id_proveedor || $o->proveedor?->estado !== 'activo');
        if ($sinProveedor->isNotEmpty()) {
            throw ValidationException::withMessages(['ordenes' => 'Sin proveedor activo: ' . $sinProveedor->map(fn ($o) => $o->materia->nombre)->join(', ')
                . '. Asígnale uno en Materia Prima → Editar.']);
        }

        $cantidades = (array) $request->input('cantidades', []);
        $compras = DB::transaction(function () use ($ordenes, $cantidades, $precios) {
            $creadas = collect();
            foreach ($ordenes->groupBy('id_proveedor') as $idProveedor => $grupo) {
                $lineas = $grupo->map(function ($o) use ($cantidades, $precios) {
                    $cant = round((float) ($cantidades[$o->id] ?? $o->cantidad_sugerida), 3);
                    // lo que nos cobró ese proveedor la última vez; si nunca le compramos, el costo del insumo
                    $deEse = ($precios[$o->id_materia] ?? collect())->firstWhere('id_proveedor', (int) $o->id_proveedor);
                    $precio = (float) ($deEse['precio'] ?? $o->materia->costo_unitario);
                    return ['orden' => $o, 'cantidad' => $cant, 'precio' => $precio, 'subtotal' => round($cant * $precio, 2)];
                });
                $compra = Compra::create([
                    'id_proveedor'  => $idProveedor,
                    'id_usuario'    => auth()->id(),
                    'fecha_compra'  => today(),
                    'incluye_igv'   => false,
                    'estado'        => 'pendiente',
                    'observaciones' => 'Generada por stock bajo (orden automática ' . $grupo->map(fn ($o) => '#' . $o->id)->join(', ') . ')',
                ] + Compra::totales($lineas->sum('subtotal'), false));

                foreach ($lineas as $l) {
                    $compra->detalles()->create([
                        'id_materia' => $l['orden']->id_materia, 'cantidad' => $l['cantidad'],
                        'precio_unitario' => $l['precio'], 'subtotal' => $l['subtotal'],
                    ]);
                    $l['orden']->update(['estado' => 'convertida', 'id_compra' => $compra->id, 'cantidad_sugerida' => $l['cantidad'], 'id_proveedor' => $idProveedor]);
                }
                $creadas->push($compra);
            }
            return $creadas;
        });

        $msg = $compras->count() === 1
            ? "Se creó la compra {$compras->first()->codigo} a {$ordenes->first()->proveedor->nombre}. Recíbela cuando llegue el pedido."
            : 'Se crearon ' . $compras->count() . ' compras (una por proveedor). Recíbelas cuando lleguen los pedidos.';

        if (!auth()->user()->hasPermiso('compras.compras')) {
            return redirect()->route('ordenes-automaticas.index')->with('success', $msg);
        }
        return ($compras->count() === 1
            ? redirect()->route('compras.show', $compras->first())
            : redirect()->route('compras.index', ['estado' => 'pendiente']))->with('success', $msg);
    }

    public function descartar(OrdenAutomatica $ordenAutomatica)
    {
        if ($ordenAutomatica->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta orden ya fue procesada.']);
        }
        $ordenAutomatica->update(['estado' => 'descartada']);
        return back()->with('success', 'Orden de ' . ($ordenAutomatica->materia->nombre ?? 'insumo') . ' descartada. No se volverá a sugerir mientras su stock no siga bajando.');
    }
}
