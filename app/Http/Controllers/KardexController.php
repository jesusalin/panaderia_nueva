<?php
namespace App\Http\Controllers;

use App\Models\KardexProducto;
use App\Models\Producto;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    public function index(Request $request)
    {
        $query = KardexProducto::with(['producto.categoria', 'usuario'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('id_producto'))
            $query->where('id_producto', $request->id_producto);
        if ($request->filled('tipo'))
            $query->where('tipo', $request->tipo);
        if ($request->filled('fecha_desde'))
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))
            $query->whereDate('created_at', '<=', $request->fecha_hasta);

        $stats = [
            'entradas' => (clone $query)->where('tipo', 'entrada')->count(),
            'salidas'  => (clone $query)->where('tipo', 'salida')->count(),
            'hoy'      => (clone $query)->whereDate('created_at', now())->count(),
            'ajustes'  => (clone $query)->where('motivo', 'ajuste_manual')->count(),
        ];

        $movimientos = $query->paginate(20)->withQueryString();
        $productos   = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('kardex.index', compact('movimientos', 'productos', 'stats'));
    }
}
