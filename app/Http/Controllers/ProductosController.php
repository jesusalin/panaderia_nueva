<?php
namespace App\Http\Controllers;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductosController extends Controller
{
    public function index(Request $request) {
        $productos = Producto::with('categoria')
            ->when($request->buscar, fn($q) => $q->where('nombre', 'like', '%'.$request->buscar.'%'))
            ->when($request->categoria, fn($q) => $q->where('id_categoria', $request->categoria))
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->orderBy('nombre')->paginate(12)->withQueryString();

        $categorias = Categoria::orderBy('nombre')->get();

        return view('productos.index', compact('productos', 'categorias'));
    }

    public function create() {
        $categorias = Categoria::where('estado','activo')->orderBy('nombre')->get();
        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'id_categoria'=>'required|exists:categorias,id',
            'nombre'=>'required|string|max:150',
            'descripcion'=>'nullable|string',
            'precio_venta'=>'required|numeric|min:0',
            'costo_produccion'=>'nullable|numeric|min:0',
            'stock_actual'=>'required|integer|min:0',
            'stock_minimo'=>'required|integer|min:0',
            'estado'=>'required|in:activo,inactivo',
            'imagen'=>'nullable|image|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        Producto::create($data);
        return redirect()->route('productos.index')->with('success','Producto creado correctamente.');
    }

    public function edit(Producto $producto) {
        $categorias = Categoria::where('estado','activo')->orderBy('nombre')->get();
        return view('productos.edit', compact('producto','categorias'));
    }

    public function update(Request $request, Producto $producto) {
        $data = $request->validate([
            'id_categoria'=>'required|exists:categorias,id',
            'nombre'=>'required|string|max:150',
            'descripcion'=>'nullable|string',
            'precio_venta'=>'required|numeric|min:0',
            'costo_produccion'=>'nullable|numeric|min:0',
            'stock_actual'=>'required|integer|min:0',
            'stock_minimo'=>'required|integer|min:0',
            'estado'=>'required|in:activo,inactivo',
            'imagen'=>'nullable|image|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) Storage::disk('public')->delete($producto->imagen);
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        if ($request->boolean('quitar_imagen') && $producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
            $data['imagen'] = null;
        }

        $producto->update($data);
        return redirect()->route('productos.index')->with('success','Producto actualizado.');
    }

    public function destroy(Producto $producto) {
        $producto->update(['estado'=>'inactivo']);
        return redirect()->route('productos.index')->with('success','Producto desactivado.');
    }
}
