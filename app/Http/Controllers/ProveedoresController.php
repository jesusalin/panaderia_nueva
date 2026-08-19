<?php
namespace App\Http\Controllers;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedoresController extends Controller
{
    public function index(Request $request) {
        $query = Proveedor::withCount(['compras', 'ordenesAutomaticas'])->orderBy('nombre');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('ruc', 'like', "%{$buscar}%")
                  ->orWhere('contacto', 'like', "%{$buscar}%");
            });
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $stats = [
            'total'    => Proveedor::count(),
            'activos'  => Proveedor::where('estado', 'activo')->count(),
            'inactivos'=> Proveedor::where('estado', 'inactivo')->count(),
            'compras'  => \App\Models\Compra::count(),
        ];

        $proveedores = $query->paginate(15)->withQueryString();
        return view('proveedores.index', compact('proveedores', 'stats'));
    }
    public function create() { return view('proveedores.create'); }
    public function store(Request $request) {
        $request->validate(['nombre'=>'required|string|max:150','ruc'=>'nullable|string|max:20','direccion'=>'nullable|string','telefono'=>'nullable|string|max:20','email'=>'nullable|email','contacto'=>'nullable|string|max:100']);
        Proveedor::create($request->all());
        return redirect()->route('proveedores.index')->with('success','Proveedor creado.');
    }
    public function edit(Proveedor $proveedor) { return view('proveedores.edit', compact('proveedor')); }
    public function update(Request $request, Proveedor $proveedor) {
        $request->validate(['nombre'=>'required|string|max:150','ruc'=>'nullable|string|max:20','direccion'=>'nullable|string','telefono'=>'nullable|string|max:20','email'=>'nullable|email','contacto'=>'nullable|string|max:100']);
        $proveedor->update($request->all());
        return redirect()->route('proveedores.index')->with('success','Proveedor actualizado.');
    }
    public function toggleEstado(Proveedor $proveedor) {
        $nuevoEstado = $proveedor->estado === 'activo' ? 'inactivo' : 'activo';
        $proveedor->update(['estado' => $nuevoEstado]);

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor ' . ($nuevoEstado === 'activo' ? 'activado' : 'desactivado') . '.');
    }

    public function destroy(Proveedor $proveedor) {
        // Medida de seguridad: para eliminar un proveedor primero hay que desactivarlo.
        // Así se evita borrar por error un proveedor que todavía está en uso.
        if ($proveedor->estado === 'activo') {
            return back()->withErrors([
                'error' => "Por seguridad, primero debes desactivar a \"{$proveedor->nombre}\" antes de poder eliminarlo. Usa el interruptor de la tarjeta para desactivarlo.",
            ]);
        }

        // Aun estando desactivado, si tiene historial asociado no se puede eliminar
        // sin romper esos registros (compras y órdenes automáticas lo referencian).
        $usos = [];
        if ($proveedor->compras()->exists()) $usos[] = 'tiene compras registradas';
        if (\App\Models\OrdenAutomatica::where('id_proveedor', $proveedor->id)->exists()) $usos[] = 'tiene órdenes automáticas asociadas';

        if (!empty($usos)) {
            return back()->withErrors([
                'error' => "No se puede eliminar \"{$proveedor->nombre}\" porque " . implode(' y ', $usos) .
                           ". Permanecerá desactivado para conservar el historial.",
            ]);
        }

        $nombre = $proveedor->nombre;
        $proveedor->delete();
        return redirect()->route('proveedores.index')->with('success', "Proveedor \"{$nombre}\" eliminado correctamente.");
    }
}
