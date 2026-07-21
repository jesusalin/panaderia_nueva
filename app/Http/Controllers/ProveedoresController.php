<?php
namespace App\Http\Controllers;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedoresController extends Controller
{
    public function index(Request $request) {
        $query = Proveedor::withCount('compras')->orderBy('nombre');

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
    public function destroy(Proveedor $proveedor) {
        $proveedor->update(['estado'=>'inactivo']);
        return redirect()->route('proveedores.index')->with('success','Proveedor desactivado.');
    }
}
