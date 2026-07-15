<?php
namespace App\Http\Controllers;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriasController extends Controller
{
    public function index() {
        $categorias = Categoria::withCount('productos')->orderBy('nombre')->paginate(12);
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

    /**
     * Elimina la categoría de verdad, solo si no tiene productos asociados
     * (si los tiene, borrarla rompería esos productos). En ese caso se
     * sugiere desactivarla en su lugar.
     */
    public function destroy(Categoria $categoria) {
        $totalProductos = $categoria->productos()->count();

        if ($totalProductos > 0) {
            return redirect()->route('categorias.index')
                ->with('error', "No se puede eliminar \"{$categoria->nombre}\": tiene {$totalProductos} producto(s) asociado(s). Desactívala si ya no la usas.");
        }

        $categoria->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada.');
    }

    /**
     * Activa/desactiva la categoría sin borrarla (para cuando tiene productos
     * y por lo tanto no se puede eliminar).
     */
    public function toggleEstado(Categoria $categoria) {
        $nuevoEstado = $categoria->estado === 'activo' ? 'inactivo' : 'activo';
        $categoria->update(['estado' => $nuevoEstado]);

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría ' . ($nuevoEstado === 'activo' ? 'activada' : 'desactivada') . '.');
    }
}
