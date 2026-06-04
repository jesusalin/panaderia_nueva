<?php
namespace App\Http\Controllers;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedoresController extends Controller
{
    public function index() {
        $proveedores = Proveedor::withCount('compras')->orderBy('nombre')->paginate(15);
        return view('proveedores.index', compact('proveedores'));
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
