<?php
namespace App\Http\Controllers;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriasController extends Controller
{
    public function index() {
        $categorias = Categoria::withCount('productos')->orderBy('nombre')->paginate(15);
        return view('categorias.index', compact('categorias'));
    }
    public function create() { return view('categorias.create'); }
    public function store(Request $request) {
        $request->validate(['nombre'=>'required|unique:categorias,nombre|max:100','descripcion'=>'nullable|string','estado'=>'required|in:activo,inactivo']);
        Categoria::create($request->all());
        return redirect()->route('categorias.index')->with('success','Categoría creada.');
    }
    public function edit(Categoria $categoria) { return view('categorias.edit', compact('categoria')); }
    public function update(Request $request, Categoria $categoria) {
        $request->validate(['nombre'=>'required|unique:categorias,nombre,'.$categoria->id.'|max:100','descripcion'=>'nullable|string','estado'=>'required|in:activo,inactivo']);
        $categoria->update($request->all());
        return redirect()->route('categorias.index')->with('success','Categoría actualizada.');
    }
    public function destroy(Categoria $categoria) {
        $categoria->update(['estado'=>'inactivo']);
        return redirect()->route('categorias.index')->with('success','Categoría desactivada.');
    }
}
