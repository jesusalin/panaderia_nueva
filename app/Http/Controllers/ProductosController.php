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
            ->withCount(['ventaDetalles', 'producciones', 'receta', 'kardex'])
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

    public function toggleEstado(Producto $producto) {
        $nuevoEstado = $producto->estado === 'activo' ? 'inactivo' : 'activo';
        $producto->update(['estado' => $nuevoEstado]);

        return redirect()->route('productos.index')
            ->with('success', 'Producto ' . ($nuevoEstado === 'activo' ? 'activado' : 'desactivado') . '.');
    }

    public function destroy(Producto $producto) {
        // Un producto con historial (ventas, producciones, receta o kardex) no se puede
        // borrar de verdad sin romper esos registros: en ese caso se pide desactivarlo.
        $usos = [];
        if ($producto->ventaDetalles()->exists())               $usos[] = 'tiene ventas registradas';
        if ($producto->producciones()->exists())                $usos[] = 'tiene producciones registradas';
        if ($producto->receta()->exists())                      $usos[] = 'tiene una receta asociada';
        if (\App\Models\KardexProducto::where('id_producto', $producto->id)->exists()) $usos[] = 'tiene movimientos de kardex';

        if (!empty($usos)) {
            return back()->withErrors([
                'error' => "No se puede eliminar \"{$producto->nombre}\" porque " . implode(' y ', $usos) .
                           ". Usa \"Desactivar\" para que deje de estar disponible sin perder su historial.",
            ]);
        }

        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }
        $nombre = $producto->nombre;
        $producto->delete();
        return redirect()->route('productos.index')->with('success', "Producto \"{$nombre}\" eliminado correctamente.");
    }
}
