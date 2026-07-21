<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Proveedor;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComprasController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'usuario']);

        if ($request->filled('id_proveedor'))
            $query->where('id_proveedor', $request->id_proveedor);
        if ($request->filled('estado'))
            $query->where('estado', $request->estado);
        if ($request->filled('fecha_desde'))
            $query->whereDate('fecha_compra', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))
            $query->whereDate('fecha_compra', '<=', $request->fecha_hasta);

        $stats = [
            'total'     => (clone $query)->count(),
            'pendientes'=> (clone $query)->where('estado', 'pendiente')->count(),
            'recibidas' => (clone $query)->where('estado', 'recibida')->count(),
            'monto_mes' => Compra::whereMonth('fecha_compra', now()->month)->whereYear('fecha_compra', now()->year)->sum('total'),
        ];

        $compras = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('compras.index', compact('compras', 'proveedores', 'stats'));
    }

    public function create()
    {
        $proveedores  = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();
        $materias     = MateriaPrima::where('estado', 'activo')->with('unidad')->orderBy('nombre')->get();
        return view('compras.create', compact('proveedores', 'materias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_proveedor'  => 'required|exists:proveedores,id',
            'fecha_compra'  => 'required|date',
            'numero_doc'    => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
            'materias'      => 'required|array|min:1',
            'materias.*.id_materia'      => 'required|exists:materia_prima,id',
            'materias.*.cantidad'        => 'required|numeric|min:0.001',
            'materias.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $detalles = [];

            foreach ($request->materias as $item) {
                $sub = round($item['cantidad'] * $item['precio_unitario'], 2);
                $subtotal += $sub;
                $detalles[] = [
                    'id_materia'      => $item['id_materia'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal'        => $sub,
                ];
            }

            $igv   = round($subtotal * 0.18, 2);
            $total = $subtotal + $igv;

            $compra = Compra::create([
                'id_proveedor'  => $request->id_proveedor,
                'id_usuario'    => auth()->id(),
                'numero_doc'    => $request->numero_doc,
                'fecha_compra'  => $request->fecha_compra,
                'subtotal'      => $subtotal,
                'igv'           => $igv,
                'total'         => $total,
                'estado'        => 'pendiente',
                'observaciones' => $request->observaciones,
            ]);

            foreach ($detalles as $detalle) {
                $detalle['id_compra'] = $compra->id;
                CompraDetalle::create($detalle);
            }

            DB::commit();
            return redirect()->route('compras.show', $compra)
                ->with('success', 'Compra registrada. Recíbela cuando llegue el pedido.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Compra $compra)
    {
        $compra->load(['proveedor', 'usuario', 'detalles.materia.unidad']);
        return view('compras.show', compact('compra'));
    }

    public function recibir(Compra $compra)
    {
        if ($compra->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta compra ya fue procesada.']);
        }

        DB::beginTransaction();
        try {
            foreach ($compra->detalles as $detalle) {
                $materia = MateriaPrima::findOrFail($detalle->id_materia);
                $stockAntes = $materia->stock_actual;
                $stockDespues = $stockAntes + $detalle->cantidad;

                $materia->update([
                    'stock_actual'  => $stockDespues,
                    'costo_unitario' => $detalle->precio_unitario, // actualiza último precio
                ]);

                MovimientoInventario::create([
                    'id_materia'    => $materia->id,
                    'id_usuario'    => auth()->id(),
                    'tipo'          => 'entrada',
                    'motivo'        => 'compra',
                    'referencia_id' => $compra->id,
                    'cantidad'      => $detalle->cantidad,
                    'stock_antes'   => $stockAntes,
                    'stock_despues' => $stockDespues,
                    'observacion'   => "Compra #{$compra->id}",
                ]);
            }

            $compra->update(['estado' => 'recibida']);
            DB::commit();
            return back()->with('success', 'Compra recibida. Stock actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function anular(Compra $compra)
    {
        if ($compra->estado === 'anulada') {
            return back()->withErrors(['error' => 'Esta compra ya está anulada.']);
        }

        $estadoOriginal = $compra->estado;

        DB::beginTransaction();
        try {
            // Si ya se había recibido, hay que revertir el stock que se sumó a la materia prima.
            // Primero se valida TODO antes de tocar nada, para no dejar reversiones a medias.
            if ($estadoOriginal === 'recibida') {
                foreach ($compra->detalles as $detalle) {
                    $materia = MateriaPrima::find($detalle->id_materia);
                    if ($materia && $materia->stock_actual < $detalle->cantidad) {
                        DB::rollBack();
                        return back()->withErrors([
                            'error' => "No se puede anular esta compra: ya se consumió parte de \"{$materia->nombre}\" que llegó con este pedido. " .
                                       "Stock actual: {$materia->stock_actual}, se necesitaría revertir {$detalle->cantidad}. " .
                                       "El stock quedaría negativo.",
                        ]);
                    }
                }

                foreach ($compra->detalles as $detalle) {
                    $materia = MateriaPrima::find($detalle->id_materia);
                    if (!$materia) continue;

                    $stockAntes   = $materia->stock_actual;
                    $stockDespues = round($stockAntes - $detalle->cantidad, 3);
                    $materia->update(['stock_actual' => $stockDespues]);

                    MovimientoInventario::create([
                        'id_materia'    => $materia->id,
                        'id_usuario'    => auth()->id(),
                        'tipo'          => 'salida',
                        'motivo'        => 'devolucion',
                        'referencia_id' => $compra->id,
                        'cantidad'      => $detalle->cantidad,
                        'stock_antes'   => $stockAntes,
                        'stock_despues' => $stockDespues,
                        'observacion'   => "Anulación de compra #{$compra->id} (registrada por error)",
                    ]);
                }
            }

            $compra->update(['estado' => 'anulada']);
            DB::commit();

            $mensaje = "Compra #{$compra->id} anulada correctamente.";
            if ($estadoOriginal === 'recibida') $mensaje .= ' El stock de materia prima fue revertido.';

            return back()->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al anular: ' . $e->getMessage()]);
        }
    }
}
