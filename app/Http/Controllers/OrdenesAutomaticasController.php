<?php
namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use App\Models\OrdenAutomatica;
use App\Models\Compra;
use App\Models\CompraDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenesAutomaticasController extends Controller
{
    /**
     * Revisa todos los insumos activos y genera una orden automática
     * pendiente para los que estén en stock bajo y no tengan ya una
     * orden pendiente abierta.
     */
    public function generar()
    {
        $materiasStockBajo = MateriaPrima::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->get();

        $creadas = 0;

        foreach ($materiasStockBajo as $materia) {
            $yaExiste = OrdenAutomatica::where('id_materia', $materia->id)
                ->where('estado', 'pendiente')->exists();

            if ($yaExiste) continue;

            // Cantidad sugerida: la configurada o el doble del mínimo por defecto
            $cantidadSugerida = $materia->cantidad_reposicion
                ?? ($materia->stock_minimo * 2);

            OrdenAutomatica::create([
                'id_materia'        => $materia->id,
                'id_proveedor'      => $materia->id_proveedor,
                'stock_al_generar'  => $materia->stock_actual,
                'stock_minimo'      => $materia->stock_minimo,
                'cantidad_sugerida' => $cantidadSugerida,
                'estado'            => 'pendiente',
            ]);
            $creadas++;
        }

        return redirect()->route('ordenes-automaticas.index')
            ->with('success', $creadas > 0
                ? "Se generaron {$creadas} nueva(s) orden(es) automática(s) de reposición."
                : "No hay insumos con stock bajo sin orden pendiente.");
    }

    public function index()
    {
        $ordenes = OrdenAutomatica::with(['materia.unidad', 'proveedor'])
            ->orderBy('created_at', 'desc')->paginate(15);
        return view('ordenes-automaticas.index', compact('ordenes'));
    }

    /**
     * Convierte una orden automática pendiente en una Compra real
     * con estado "pendiente" (a la espera de ser recibida normalmente).
     */
    public function convertir(OrdenAutomatica $ordenAutomatica)
    {
        if ($ordenAutomatica->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta orden ya fue procesada.']);
        }

        if (!$ordenAutomatica->id_proveedor) {
            return back()->withErrors(['error' => 'Este insumo no tiene un proveedor asignado. Edítalo primero en Materia Prima.']);
        }

        DB::beginTransaction();
        try {
            $materia = $ordenAutomatica->materia;
            $cantidad = $ordenAutomatica->cantidad_sugerida;
            $precio   = $materia->costo_unitario;
            $subtotal = round($cantidad * $precio, 2);
            $igv      = round($subtotal * 0.18, 2);
            $total    = $subtotal + $igv;

            $compra = Compra::create([
                'id_proveedor'  => $ordenAutomatica->id_proveedor,
                'id_usuario'    => auth()->id(),
                'numero_doc'    => null,
                'fecha_compra'  => now(),
                'subtotal'      => $subtotal,
                'igv'           => $igv,
                'total'         => $total,
                'estado'        => 'pendiente',
                'observaciones' => "Generada automáticamente por stock bajo (Orden Automática #{$ordenAutomatica->id})",
            ]);

            CompraDetalle::create([
                'id_compra'       => $compra->id,
                'id_materia'      => $materia->id,
                'cantidad'        => $cantidad,
                'precio_unitario' => $precio,
                'subtotal'        => $subtotal,
            ]);

            $ordenAutomatica->update([
                'id_compra' => $compra->id,
                'estado'    => 'convertida',
            ]);

            DB::commit();
            return redirect()->route('compras.show', $compra)
                ->with('success', 'Orden convertida en compra. Recíbela cuando llegue el pedido.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function descartar(OrdenAutomatica $ordenAutomatica)
    {
        if ($ordenAutomatica->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta orden ya fue procesada.']);
        }
        $ordenAutomatica->update(['estado' => 'descartada']);
        return back()->with('success', 'Orden automática descartada.');
    }
}
