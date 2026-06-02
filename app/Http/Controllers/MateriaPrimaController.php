<?php
namespace App\Http\Controllers;
use App\Models\MateriaPrima;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;

class MateriaPrimaController extends Controller
{
    public function index() {
        $materias = MateriaPrima::with('unidad')->orderBy('nombre')->paginate(15);
        return view('materia_prima.index', compact('materias'));
    }
    public function create() {
        $unidades = UnidadMedida::orderBy('nombre')->get();
        return view('materia_prima.create', compact('unidades'));
    }
    public function store(Request $request) {
        $request->validate(['nombre'=>'required|string|max:150','id_unidad'=>'required|exists:unidades_medida,id','stock_actual'=>'required|numeric|min:0','stock_minimo'=>'required|numeric|min:0','costo_unitario'=>'required|numeric|min:0','estado'=>'required|in:activo,inactivo']);
        MateriaPrima::create($request->all());
        return redirect()->route('materia-prima.index')->with('success','Materia prima creada.');
    }
    public function edit(MateriaPrima $materiaPrima) {
        $unidades = UnidadMedida::orderBy('nombre')->get();
        return view('materia_prima.edit', compact('materiaPrima','unidades'));
    }
    public function update(Request $request, MateriaPrima $materiaPrima) {
        $request->validate(['nombre'=>'required|string|max:150','id_unidad'=>'required|exists:unidades_medida,id','stock_actual'=>'required|numeric|min:0','stock_minimo'=>'required|numeric|min:0','costo_unitario'=>'required|numeric|min:0','estado'=>'required|in:activo,inactivo']);
        $materiaPrima->update($request->all());
        return redirect()->route('materia-prima.index')->with('success','Materia prima actualizada.');
    }
    public function destroy(MateriaPrima $materiaPrima) {
        $materiaPrima->update(['estado'=>'inactivo']);
        return redirect()->route('materia-prima.index')->with('success','Materia prima desactivada.');
    }
}
