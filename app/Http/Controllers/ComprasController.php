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
    public function index()
    {
        $compras = Compra::with(['proveedor', 'usuario'])
            ->orderBy('created_at', 'desc')->paginate(15);
        return view('compras.index', compact('compras'));
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
}
