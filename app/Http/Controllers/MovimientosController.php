<?php
namespace App\Http\Controllers;
use App\Models\MovimientoInventario;
use App\Models\MateriaPrima;
use Illuminate\Http\Request;

class MovimientosController extends Controller
{
    public function index(Request $request) {
        $query = MovimientoInventario::with(['materia.unidad','usuario'])->orderBy('created_at','desc');
        if ($request->filled('id_materia')) $query->where('id_materia',$request->id_materia);
        if ($request->filled('tipo')) $query->where('tipo',$request->tipo);

        $stats = [
            'entradas' => (clone $query)->where('tipo', 'entrada')->count(),
            'salidas'  => (clone $query)->where('tipo', 'salida')->count(),
            'ajustes'  => (clone $query)->where('tipo', 'ajuste')->count(),
            'hoy'      => (clone $query)->whereDate('created_at', now())->count(),
        ];

        $movimientos = $query->paginate(20)->withQueryString();
        $materias = MateriaPrima::where('estado','activo')->orderBy('nombre')->get();
        return view('movimientos.index', compact('movimientos','materias','stats'));
    }
}
