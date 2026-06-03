<?php
namespace App\Http\Controllers;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClientesController extends Controller
{
    public function index() {
        $clientes = Cliente::withCount('ventas')
            ->orderBy('nombre')
            ->paginate(15);
        return view('clientes.index', compact('clientes'));
    }

    public function create() {
        return view('clientes.create');
    }

    public function store(Request $request) {
        $request->validate([
            'nombre'     => 'required|string|max:150',
            'tipo'       => 'required|in:bodega,supermercado,colegio,restaurante,panaderia,particular,otro',
            'ruc'        => 'nullable|string|max:20',
            'dni'        => 'nullable|string|max:15',
            'telefono'   => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:150',
            'direccion'  => 'nullable|string|max:255',
            'distrito'   => 'nullable|string|max:100',
            'referencia' => 'nullable|string|max:255',
        ]);
        Cliente::create($request->all());
        return redirect()->route('clientes.index')
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Cliente $cliente) {
        $ventas = $cliente->ventas()
            ->with('detalles.producto')
            ->orderByDesc('fecha_venta')
            ->paginate(10);
        $totalComprado = $cliente->totalComprado();
        return view('clientes.show', compact('cliente', 'ventas', 'totalComprado'));
    }

    public function edit(Cliente $cliente) {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente) {
        $request->validate([
            'nombre'     => 'required|string|max:150',
            'tipo'       => 'required|in:bodega,supermercado,colegio,restaurante,panaderia,particular,otro',
            'ruc'        => 'nullable|string|max:20',
            'dni'        => 'nullable|string|max:15',
            'telefono'   => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:150',
            'direccion'  => 'nullable|string|max:255',
            'distrito'   => 'nullable|string|max:100',
            'referencia' => 'nullable|string|max:255',
        ]);
        $cliente->update($request->all());
        return redirect()->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente) {
        if ($cliente->nombre === 'Cliente General') {
            return redirect()->route('clientes.index')
                ->with('error', 'No se puede eliminar el Cliente General.');
        }
        $cliente->update(['estado' => 'inactivo']);
        return redirect()->route('clientes.index')
            ->with('success', 'Cliente desactivado correctamente.');
    }
}
