<?php
namespace App\Http\Controllers;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClientesController extends Controller
{
    public function index(Request $request) {
        $clientes = Cliente::withCount('ventas')
            ->when($request->buscar, fn($q) => $q->where(function($qq) use ($request) {
                $qq->where('nombre', 'like', '%'.$request->buscar.'%')
                   ->orWhere('distrito', 'like', '%'.$request->buscar.'%');
            }))
            ->when($request->tipo, fn($q) => $q->where('tipo', $request->tipo))
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total'      => Cliente::count(),
            'activos'    => Cliente::where('estado', 'activo')->count(),
            'distritos'  => Cliente::whereNotNull('distrito')->where('distrito', '!=', '')->distinct('distrito')->count('distrito'),
            'mayoristas' => Cliente::whereIn('tipo', ['bodega', 'supermercado', 'colegio', 'restaurante'])->count(),
        ];

        return view('clientes.index', compact('clientes', 'stats'));
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
        $ventasCompletadas = $cliente->ventas()->where('estado', 'completada')->count();
        $ticketPromedio = $ventasCompletadas > 0 ? $totalComprado / $ventasCompletadas : 0;

        // Si la petición viene por AJAX (clic en "Ver detalle" desde el listado),
        // devolvemos solo el fragmento con el contenido, sin el layout completo,
        // para poder mostrarlo en un modal sin recargar la página.
        if (request()->ajax()) {
            return view('clientes._detalle', compact('cliente', 'ventas', 'totalComprado', 'ventasCompletadas', 'ticketPromedio'));
        }

        return view('clientes.show', compact('cliente', 'ventas', 'totalComprado', 'ventasCompletadas', 'ticketPromedio'));
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
