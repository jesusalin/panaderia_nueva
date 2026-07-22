<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\KardexProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentasController extends Controller
{
    public function index()
    {
        $ventas = Venta::with(['usuario', 'cliente'])
            ->orderBy('created_at', 'desc')->paginate(15);

        $hoy = Venta::whereDate('fecha_venta', today())->where('estado', 'completada');

        $stats = [
            'ventas_hoy'    => (clone $hoy)->count(),
            'ingresos_hoy'  => (clone $hoy)->sum('total'),
            'ticket_prom'   => (clone $hoy)->count() > 0 ? (clone $hoy)->sum('total') / (clone $hoy)->count() : 0,
            'pago_top'      => (clone $hoy)->select('tipo_pago', DB::raw('COUNT(*) as total'))
                                    ->groupBy('tipo_pago')->orderByDesc('total')->first(),
        ];

        return view('ventas.index', compact('ventas', 'stats'));
    }

    public function create()
    {
        $productos = Producto::where('estado', 'activo')->where('stock_actual', '>', 0)
            ->with('categoria')->orderBy('nombre')->get();
        $clientes  = Cliente::orderBy('nombre')->get();
        return view('ventas.create', compact('productos', 'clientes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_cliente'    => 'nullable|exists:clientes,id',
            'tipo_pago'     => 'required|in:efectivo,yape,plin,tarjeta,otro',
            'observaciones' => 'nullable|string',
            'productos'     => 'required|array|min:1',
            'productos.*.id_producto' => 'required|exists:productos,id',
            'productos.*.cantidad'    => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $detalles = [];

            foreach ($request->productos as $item) {
                $producto = Producto::findOrFail($item['id_producto']);
                if ($producto->stock_actual < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para: {$producto->nombre}");
                }
                $sub = $producto->precio_venta * $item['cantidad'];
                $subtotal += $sub;
                $detalles[] = [
                    'id_producto'     => $producto->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal'        => $sub,
                ];
            }

            $igv   = round($subtotal * 0.18, 2);
            $total = $subtotal + $igv;

            $numeroVenta = 'V-' . str_pad(
                (Venta::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT
            );

            $venta = Venta::create([
                'id_usuario'    => auth()->id(),
                'id_cliente'    => $request->id_cliente ?? 1,
                'numero_venta'  => $numeroVenta,
                'fecha_venta'   => Carbon::now(),
                'subtotal'      => $subtotal,
                'igv'           => $igv,
                'total'         => $total,
                'tipo_pago'     => $request->tipo_pago,
                'estado'        => 'completada',
                'observaciones' => $request->observaciones,
            ]);

            foreach ($detalles as $detalle) {
                $detalle['id_venta'] = $venta->id;
                VentaDetalle::create($detalle);

                // Descontar stock y registrar en Kardex
                $producto     = Producto::find($detalle['id_producto']);
                $stockAntes   = $producto->stock_actual;
                $stockDespues = $stockAntes - $detalle['cantidad'];

                $producto->update(['stock_actual' => $stockDespues]);

                KardexProducto::create([
                    'id_producto'   => $detalle['id_producto'],
                    'id_usuario'    => auth()->id(),
                    'tipo'          => 'salida',
                    'motivo'        => 'venta',
                    'referencia_id' => $venta->id,
                    'cantidad'      => $detalle['cantidad'],
                    'stock_antes'   => $stockAntes,
                    'stock_despues' => $stockDespues,
                    'observacion'   => "Venta {$numeroVenta}",
                ]);
            }

            DB::commit();
            return redirect()->route('ventas.show', $venta)
                ->with('success', "Venta {$numeroVenta} registrada correctamente.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Venta $venta)
    {
        $venta->load(['usuario', 'cliente', 'detalles.producto.categoria']);

        // Si la petición viene por AJAX (clic en "Ver detalle" desde el listado),
        // devolvemos solo el fragmento con el contenido, sin el layout completo,
        // para poder mostrarlo en un modal sin recargar la página.
        if (request()->ajax()) {
            return view('ventas._detalle', compact('venta'));
        }

        return view('ventas.show', compact('venta'));
    }

    public function anular(Venta $venta)
    {
        if ($venta->estado === 'anulada') {
            return back()->withErrors(['error' => 'La venta ya está anulada.']);
        }

        DB::beginTransaction();
        try {
            foreach ($venta->detalles as $detalle) {
                $producto     = Producto::find($detalle->id_producto);
                $stockAntes   = $producto->stock_actual;
                $stockDespues = $stockAntes + $detalle->cantidad;

                $producto->update(['stock_actual' => $stockDespues]);

                KardexProducto::create([
                    'id_producto'   => $detalle->id_producto,
                    'id_usuario'    => auth()->id(),
                    'tipo'          => 'entrada',
                    'motivo'        => 'devolucion',
                    'referencia_id' => $venta->id,
                    'cantidad'      => $detalle->cantidad,
                    'stock_antes'   => $stockAntes,
                    'stock_despues' => $stockDespues,
                    'observacion'   => "Anulación venta {$venta->numero_venta}",
                ]);
            }
            $venta->update(['estado' => 'anulada']);
            DB::commit();
            return back()->with('success', 'Venta anulada y stock restaurado.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Venta $venta)
    {
        DB::beginTransaction();
        try {
            // Si la venta seguía "completada" (no había sido anulada antes), el stock
            // vendido todavía está descontado del inventario: hay que devolverlo antes
            // de borrar el registro, igual que hace anular().
            if ($venta->estado === 'completada') {
                foreach ($venta->detalles as $detalle) {
                    $producto     = Producto::find($detalle->id_producto);
                    $stockAntes   = $producto->stock_actual;
                    $stockDespues = $stockAntes + $detalle->cantidad;

                    $producto->update(['stock_actual' => $stockDespues]);

                    KardexProducto::create([
                        'id_producto'   => $detalle->id_producto,
                        'id_usuario'    => auth()->id(),
                        'tipo'          => 'entrada',
                        'motivo'        => 'devolucion',
                        'referencia_id' => null,
                        'cantidad'      => $detalle->cantidad,
                        'stock_antes'   => $stockAntes,
                        'stock_despues' => $stockDespues,
                        'observacion'   => "Eliminación venta {$venta->numero_venta}",
                    ]);
                }
            }

            $numeroVenta = $venta->numero_venta;
            $venta->detalles()->delete();
            $venta->delete();

            DB::commit();
            return redirect()->route('ventas.index')
                ->with('success', "Venta {$numeroVenta} eliminada correctamente.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
