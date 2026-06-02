<?php
namespace App\Http\Controllers;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;

class ProductosController extends Controller
{
    public function index() {
        $productos = Producto::with('categoria')->orderBy('nombre')->paginate(15);
        return view('productos.index', compact('productos'));
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
        ]);
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
        ]);
        $producto->update($data);
        return redirect()->route('productos.index')->with('success','Producto actualizado.');
    }
    public function destroy(Producto $producto) {
        $producto->update(['estado'=>'inactivo']);
        return redirect()->route('productos.index')->with('success','Producto desactivado.');
    }
}
