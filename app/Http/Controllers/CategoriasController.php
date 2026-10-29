<?php
namespace App\Http\Controllers;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriasController extends Controller
{
    public function index(Request $request) {
        $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => 'nullable|in:activo,inactivo',
        ]);

        $categorias = Categoria::withCount([
                'productos',
                'productos as productos_activos_count'   => fn ($q) => $q->where('estado', 'activo'),
                'productos as productos_stock_bajo_count' => fn ($q) => $q->where('estado', 'activo')->whereColumn('stock_actual', '<=', 'stock_minimo'),
            ])
            ->when($request->filled('buscar'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', '%' . $request->buscar . '%')
                ->orWhere('descripcion', 'like', '%' . $request->buscar . '%')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->orderBy('nombre')
            ->paginate(12)->withQueryString();

        $stats = [
            'total'      => Categoria::count(),
            'activas'    => Categoria::where('estado', 'activo')->count(),
            'vacias'     => Categoria::doesntHave('productos')->count(),
        ];

        return view('categorias.index', compact('categorias', 'stats'));
    }

    /** Ficha de la categoría: sus productos. */
    public function show(Categoria $categoria) {
        $productos = $categoria->productos()->orderByDesc('estado')->orderBy('nombre')->get();
        return view('categorias.show', compact('categoria', 'productos'));
    }

    public function create() { return view('categorias.create'); }

    public function store(Request $request) {
        $datos = $request->validate($this->reglas());
        Categoria::create($datos);
        return redirect()->route('categorias.index')->with('success','Categoría creada.');
    }

    public function edit(Categoria $categoria) { return view('categorias.edit', compact('categoria')); }

    public function update(Request $request, Categoria $categoria) {
        $datos = $request->validate($this->reglas($categoria));
        $categoria->update($datos);
        return redirect()->route('categorias.index')->with('success','Categoría actualizada.');
    }

    /**
     * Elimina la categoría de verdad, solo si no tiene productos asociados
     * (si los tiene, borrarla rompería esos productos). Para vaciarla se cambia
     * la categoría de cada producto desde su "Editar".
     */
    public function destroy(Categoria $categoria) {
        $totalProductos = $categoria->productos()->count();

        if ($totalProductos > 0) {
            return redirect()->route('categorias.index')
                ->with('error', "No se puede eliminar \"{$categoria->nombre}\": tiene {$totalProductos} producto(s). Cámbiales la categoría desde Editar o desactívala.");
        }

        $categoria->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada.');
    }

    /**
     * Activa/desactiva la categoría sin borrarla.
     */
    public function toggleEstado(Categoria $categoria) {
        $nuevoEstado = $categoria->estado === 'activo' ? 'inactivo' : 'activo';
        $categoria->update(['estado' => $nuevoEstado]);

        return back()->with('success', 'Categoría ' . ($nuevoEstado === 'activo' ? 'activada' : 'desactivada') . '.');
    }

    private function reglas(?Categoria $categoria = null): array {
        return [
            'nombre'      => ['required', 'string', 'max:100', Rule::unique('categorias', 'nombre')->ignore($categoria?->id)],
            'descripcion' => 'nullable|string|max:255',
            'icono'       => ['nullable', Rule::in(array_keys(Categoria::ICONOS))],
            'color'       => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'estado'      => 'required|in:activo,inactivo',
        ];
    }
}
