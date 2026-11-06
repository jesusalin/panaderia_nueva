<?php

namespace App\Http\Controllers;

use App\Exports\VentasExport;
use App\Models\Cliente;
use App\Models\KardexProducto;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class VentasController extends Controller
{
    public const PERIODOS = ['hoy' => 'Hoy', 'ayer' => 'Ayer', '7' => '7 días', 'mes' => 'Este mes', 'todo' => 'Todo'];

    /** Rango de fechas del filtro (null = sin límite) */
    private function rango(Request $request): array
    {
        $desde = $request->input('desde'); $hasta = $request->input('hasta');
        if ($desde || $hasta) {
            return [null, $desde ? Carbon::parse($desde)->startOfDay() : null, $hasta ? Carbon::parse($hasta)->endOfDay() : null];
        }
        $p = $request->input('periodo', 'todo');
        if (!isset(self::PERIODOS[$p])) $p = 'todo';
        [$d, $h] = match ($p) {
            'hoy'  => [today(), today()->endOfDay()],
            'ayer' => [today()->subDay(), today()->subDay()->endOfDay()],
            '7'    => [today()->subDays(6), today()->endOfDay()],
            'mes'  => [today()->startOfMonth(), today()->endOfDay()],
            default => [null, null],
        };
        return [$p, $d, $h];
    }

    // ─────────────────────────────── LISTADO ───────────────────────────────
    public function index(Request $request)
    {
        $request->validate([
            'buscar' => 'nullable|string|max:100', 'tipo_pago' => ['nullable', Rule::in(array_keys(Venta::MEDIOS))],
            'estado' => 'nullable|in:completada,anulada', 'vendedor' => 'nullable|integer', 'desde' => 'nullable|date', 'hasta' => 'nullable|date',
        ]);
        [$periodo, $desde, $hasta] = $this->rango($request);

        $query = Venta::with(['cliente', 'usuario', 'detalles.producto']);
        if ($request->filled('buscar')) {
            $b = trim($request->buscar);
            $query->where(fn ($q) => $q->where('numero_venta', 'like', "%{$b}%")
                ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$b}%"))
                ->orWhereHas('detalles.producto', fn ($p) => $p->where('nombre', 'like', "%{$b}%")));
        }
        if ($request->filled('tipo_pago')) $query->where('tipo_pago', $request->tipo_pago);
        if ($request->filled('estado'))    $query->where('estado', $request->estado);
        if ($request->filled('vendedor'))  $query->where('id_usuario', $request->vendedor);
        if ($desde) $query->where('fecha_venta', '>=', $desde);
        if ($hasta) $query->where('fecha_venta', '<=', $hasta);

        $filtrado = [
            'n'     => (clone $query)->where('estado', 'completada')->count(),
            'total' => (float) (clone $query)->where('estado', 'completada')->sum('total'),
            'anuladas' => (clone $query)->where('estado', 'anulada')->count(),
        ];

        // Caja de hoy (no depende de los filtros)
        $hoy = Venta::whereDate('fecha_venta', today())->where('estado', 'completada');
        $ayer = Venta::whereDate('fecha_venta', today()->subDay())->where('estado', 'completada');
        $caja = [
            'n'        => (clone $hoy)->count(),
            'total'    => (float) (clone $hoy)->sum('total'),
            'ayer'     => (float) (clone $ayer)->sum('total'),
            'anuladas' => Venta::whereDate('fecha_venta', today())->where('estado', 'anulada')->count(),
            'medios'   => (clone $hoy)->select('tipo_pago', DB::raw('COUNT(*) as n'), DB::raw('SUM(total) as total'))->groupBy('tipo_pago')->get()->keyBy('tipo_pago'),
        ];
        $caja['ticket'] = $caja['n'] ? $caja['total'] / $caja['n'] : 0;
        $topHoy = VentaDetalle::whereHas('venta', fn ($v) => $v->whereDate('fecha_venta', today())->where('estado', 'completada'))
            ->select('id_producto', DB::raw('SUM(cantidad) as cant'))->groupBy('id_producto')->orderByDesc('cant')->with('producto')->limit(3)->get();

        $ventas = $query->orderByDesc('fecha_venta')->orderByDesc('id')->paginate(30)->withQueryString();
        $vendedores = Usuario::whereIn('id', Venta::select('id_usuario')->distinct())->orderBy('nombre')->get();

        return view('ventas.index', compact('ventas', 'caja', 'topHoy', 'filtrado', 'periodo', 'vendedores'));
    }

    // ─────────────────────────────── NUEVA VENTA ───────────────────────────────
    /** Las ventas se registran desde Productos (venta rápida); esta dirección lleva allí. */
    public function create()
    {
        return redirect()->route('productos.index');
    }

    private function idClienteGeneral(): ?int
    {
        return Cliente::where('nombre', 'Cliente General')->value('id') ?? Cliente::orderBy('id')->value('id');
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_cliente'       => 'nullable|exists:clientes,id',
            'tipo_pago'        => ['required', Rule::in(array_keys(Venta::MEDIOS))],
            'monto_recibido'   => 'nullable|numeric|min:0',
            'numero_operacion' => 'nullable|string|max:30',
            'observaciones'    => 'nullable|string|max:300',
            'productos'        => 'required|array|min:1',
            'productos.*.id_producto' => 'required|distinct|exists:productos,id',
            'productos.*.cantidad'    => 'required|integer|min:1',
        ], [
            'productos.required' => 'Agrega al menos un producto.',
            'productos.*.id_producto.distinct' => 'Un producto está repetido: junta las cantidades.',
        ]);

        try {
            $venta = DB::transaction(function () use ($request) {
                $total = 0;
                $lineas = [];
                foreach ($request->productos as $item) {
                    // Se bloquea el producto: dos cajas no pueden vender la misma última unidad
                    $p = Producto::lockForUpdate()->findOrFail($item['id_producto']);
                    $cant = (int) $item['cantidad'];
                    if ($p->estado !== 'activo') throw new \RuntimeException("\"{$p->nombre}\" está inactivo.");
                    if ($p->stock_actual < $cant) {
                        throw new \RuntimeException("Stock insuficiente para: {$p->nombre} (hay {$p->stock_actual}, pediste {$cant}).");
                    }
                    $sub = round($p->precio_venta * $cant, 2);
                    $total += $sub;
                    $lineas[] = [$p, $cant, (float) $p->precio_venta, $sub];
                }
                $t = Venta::totales($total);

                // Pago: en efectivo se calcula el vuelto; si se escribió lo recibido, debe alcanzar
                $recibido = $request->filled('monto_recibido') ? round((float) $request->monto_recibido, 2) : null;
                if ($request->tipo_pago === 'efectivo' && $recibido !== null && $recibido + 0.001 < $t['total']) {
                    throw ValidationException::withMessages(['monto_recibido' => 'Lo recibido (S/ ' . number_format($recibido, 2) . ') no alcanza para el total (S/ ' . number_format($t['total'], 2) . ').']);
                }

                $venta = Venta::create([
                    'id_usuario'       => auth()->id(),
                    'id_cliente'       => $request->id_cliente ?: $this->idClienteGeneral(),
                    'numero_venta'     => 'TMP-' . uniqid(),
                    'fecha_venta'      => now(),
                    'tipo_pago'        => $request->tipo_pago,
                    'monto_recibido'   => $request->tipo_pago === 'efectivo' ? $recibido : null,
                    'vuelto'           => $request->tipo_pago === 'efectivo' && $recibido !== null ? round($recibido - $t['total'], 2) : null,
                    'numero_operacion' => $request->tipo_pago !== 'efectivo' ? ($request->numero_operacion ?: null) : null,
                    'estado'           => 'completada',
                    'observaciones'    => $request->observaciones,
                ] + $t);
                // El número sale del id: nunca se repite aunque vendan dos cajas a la vez
                $venta->update(['numero_venta' => 'V-' . str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT)]);

                foreach ($lineas as [$p, $cant, $precio, $sub]) {
                    $venta->detalles()->create(['id_producto' => $p->id, 'cantidad' => $cant, 'precio_unitario' => $precio, 'subtotal' => $sub]);
                    $antes = (int) $p->stock_actual;
                    $p->update(['stock_actual' => $antes - $cant]);
                    KardexProducto::create([
                        'id_producto' => $p->id, 'id_usuario' => auth()->id(), 'tipo' => 'salida', 'motivo' => 'venta',
                        'referencia_id' => $venta->id, 'cantidad' => $cant, 'stock_antes' => $antes, 'stock_despues' => $antes - $cant,
                        'observacion' => "Venta {$venta->numero_venta}", 'created_at' => now(),
                    ]);
                }
                return $venta;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        $msg = "Venta {$venta->numero_venta} registrada: S/ " . number_format($venta->total, 2) . '.';
        if ($venta->vuelto !== null) $msg .= ' Vuelto: S/ ' . number_format($venta->vuelto, 2) . '.';
        // Desde la venta rápida de Productos se vuelve allí para seguir vendiendo
        if ($request->input('volver') === 'productos') {
            $url = url()->previous();
            $destino = str_starts_with($url, route('productos.index')) ? $url : route('productos.index');
            return redirect()->to($destino)->with('ultima_venta', $venta->id);   // allí se muestra el aviso con el ticket
        }
        return redirect()->route('ventas.show', $venta)->with('success', $msg);
    }

    // ─────────────────────────────── DETALLE / COMPROBANTES ───────────────────────────────
    public function show(Venta $venta)
    {
        $venta->load(['usuario', 'usuarioAnula', 'cliente', 'detalles.producto.categoria']);
        if (request()->ajax()) {
            return view('ventas._detalle', compact('venta'));
        }
        return view('ventas.show', compact('venta'));
    }

    /** Ticket para impresora térmica de 80 mm (se imprime desde el navegador) */
    public function ticket(Venta $venta)
    {
        $venta->load(['usuario', 'cliente', 'detalles.producto']);
        return view('ventas.ticket', compact('venta'));
    }

    public function pdf(Venta $venta)
    {
        $venta->load(['usuario', 'usuarioAnula', 'cliente', 'detalles.producto']);
        return Pdf::loadView('ventas.pdf', compact('venta'))->setPaper('a4')->stream("comprobante-{$venta->numero_venta}.pdf");
    }

    public function exportarExcel(Request $request)
    {
        [, $desde, $hasta] = $this->rango($request);
        $fd = $desde?->toDateString() ?? $request->get('fecha_desde');
        $fh = $hasta?->toDateString() ?? $request->get('fecha_hasta');
        $nombre = 'ventas' . ($fd || $fh ? "_{$fd}_a_{$fh}" : '') . '.xlsx';
        return Excel::download(new VentasExport($fd, $fh), $nombre);
    }

    // ─────────────────────────────── ANULAR ───────────────────────────────
    public function anular(Request $request, Venta $venta)
    {
        if ($venta->estado === 'anulada') {
            return back()->withErrors(['error' => 'La venta ya está anulada.']);
        }
        $request->validate(['motivo_anulacion' => 'required|string|min:4|max:200'], [
            'motivo_anulacion.required' => 'Escribe por qué anulas la venta.',
            'motivo_anulacion.min'      => 'Escribe por qué anulas la venta.',
        ]);

        DB::transaction(function () use ($venta, $request) {
            foreach ($venta->detalles as $d) {
                $p = Producto::lockForUpdate()->find($d->id_producto);
                if (!$p) continue;
                $antes = (int) $p->stock_actual;
                $p->update(['stock_actual' => $antes + $d->cantidad]);
                KardexProducto::create([
                    'id_producto' => $p->id, 'id_usuario' => auth()->id(), 'tipo' => 'entrada', 'motivo' => 'devolucion',
                    'referencia_id' => $venta->id, 'cantidad' => $d->cantidad, 'stock_antes' => $antes, 'stock_despues' => $antes + $d->cantidad,
                    'observacion' => "Anulación de la venta {$venta->numero_venta}: {$request->motivo_anulacion}", 'created_at' => now(),
                ]);
            }
            $venta->update([
                'estado' => 'anulada', 'anulada_at' => now(), 'id_usuario_anula' => auth()->id(), 'motivo_anulacion' => $request->motivo_anulacion,
            ]);
        });

        return redirect()->route('ventas.show', $venta)->with('success', "Venta {$venta->numero_venta} anulada: los productos volvieron al stock (quedó en el kardex).");
    }

    /** Las ventas no se borran: se anulan, para que quede el registro (y el kardex cuadre). */
    public function destroy(Venta $venta)
    {
        return back()->withErrors(['error' => "Las ventas no se eliminan. Si {$venta->numero_venta} fue un error, anúlala: así queda el registro y el stock vuelve."]);
    }
}
