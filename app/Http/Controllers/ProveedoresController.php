<?php
namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\MateriaPrima;
use App\Models\OrdenAutomatica;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedoresController extends Controller
{
    public const ORDENES = ['nombre' => 'Nombre (A-Z)', 'gasto' => 'Más comprado', 'reciente' => 'Última compra', 'pendientes' => 'Con pedidos por recibir'];

    public function index(Request $request)
    {
        $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => 'nullable|in:activo,inactivo',
            'orden'  => ['nullable', Rule::in(array_keys(self::ORDENES))],
        ]);
        $valida = fn ($q) => $q->where('estado', '!=', 'anulada');

        $query = Proveedor::withCount(['compras' => $valida, 'insumos', 'compras as pendientes_count' => fn ($q) => $q->where('estado', 'pendiente')])
            ->withSum(['compras as total_comprado' => $valida], 'total')
            ->withMax(['compras as ultima_compra' => $valida], 'fecha_compra')
            ->withCount(['ordenesAutomaticas as ordenes_pendientes' => fn ($q) => $q->where('estado', 'pendiente')]);

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(fn ($q) => $q->where('nombre', 'like', "%{$b}%")->orWhere('ruc', 'like', "%{$b}%")
                ->orWhere('contacto', 'like', "%{$b}%")->orWhere('telefono', 'like', "%{$b}%"));
        }
        if ($request->filled('estado')) $query->where('estado', $request->estado);

        match ($request->orden) {
            'gasto'      => $query->orderByDesc('total_comprado'),
            'reciente'   => $query->orderByDesc('ultima_compra'),
            'pendientes' => $query->orderByDesc('pendientes_count')->orderBy('nombre'),
            default      => $query->orderBy('nombre'),
        };

        $stats = [
            'total'      => Proveedor::count(),
            'activos'    => Proveedor::where('estado', 'activo')->count(),
            'inactivos'  => Proveedor::where('estado', 'inactivo')->count(),
            'gasto_mes'  => (float) Compra::where('estado', '!=', 'anulada')->whereDate('fecha_compra', '>=', today()->startOfMonth())->sum('total'),
            'sin_insumos'=> Proveedor::where('estado', 'activo')->doesntHave('insumos')->count(),
            'insumos_sin_proveedor' => MateriaPrima::where('estado', 'activo')->whereNull('id_proveedor')->count(),
        ];

        $proveedores = $query->paginate(15)->withQueryString();
        return view('proveedores.index', compact('proveedores', 'stats'));
    }

    public function show(Proveedor $proveedor)
    {
        $valida = Compra::where('id_proveedor', $proveedor->id)->where('estado', '!=', 'anulada');
        $resumen = [
            'total'      => (float) (clone $valida)->sum('total'),
            'compras'    => (clone $valida)->count(),
            'pendientes' => Compra::where('id_proveedor', $proveedor->id)->where('estado', 'pendiente')->count(),
            'ultima'     => (clone $valida)->max('fecha_compra'),
            'anio'       => (float) (clone $valida)->whereYear('fecha_compra', now()->year)->sum('total'),
        ];
        $resumen['promedio'] = $resumen['compras'] ? $resumen['total'] / $resumen['compras'] : 0;

        // Lo comprado en los últimos 6 meses (para el gráfico de barras)
        $meses = collect(range(5, 0))->map(function ($i) use ($proveedor) {
            $m = now()->startOfMonth()->subMonthsNoOverflow($i);
            return [
                'mes'   => $m,
                'total' => (float) Compra::where('id_proveedor', $proveedor->id)->where('estado', '!=', 'anulada')
                    ->whereBetween('fecha_compra', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])->sum('total'),
            ];
        });

        // Insumos que le compramos: los asignados + los que aparecen en sus compras, con su último precio
        $asignados = $proveedor->insumos()->with('unidad')->orderBy('nombre')->get();
        $ultimosPrecios = CompraDetalle::whereHas('compra', fn ($q) => $q->where('id_proveedor', $proveedor->id)->where('estado', '!=', 'anulada'))
            ->with(['materia.unidad', 'compra:id,fecha_compra,incluye_igv'])->orderByDesc('id_compra')->get()
            ->groupBy('id_materia')->map(function ($filas) {
                $u = $filas->first(); $previo = $filas->get(1);
                $neto = fn ($d) => $d->compra->incluye_igv ? $d->precio_unitario / 1.18 : $d->precio_unitario;
                return [
                    'materia' => $u->materia, 'precio' => $neto($u), 'fecha' => $u->compra->fecha_compra,
                    'var'     => $previo ? round(($neto($u) - $neto($previo)) / max($neto($previo), 0.01) * 100) : null,
                    'veces'   => $filas->count(),
                    'cantidad'=> $filas->sum(fn ($d) => $d->cantidad_real),
                ];
            });
        $insumos = $asignados->map(fn ($m) => ['materia' => $m, 'asignado' => true] + ($ultimosPrecios[$m->id] ?? ['precio' => null, 'fecha' => null, 'var' => null, 'veces' => 0, 'cantidad' => 0]))
            ->concat($ultimosPrecios->reject(fn ($x, $id) => $asignados->contains('id', $id))->map(fn ($x) => $x + ['asignado' => false]))
            ->values();

        $compras = Compra::where('id_proveedor', $proveedor->id)->with('detalles.materia')->orderByDesc('fecha_compra')->orderByDesc('id')->limit(10)->get();
        $ordenes = OrdenAutomatica::where('id_proveedor', $proveedor->id)->where('estado', 'pendiente')->with('materia.unidad')->get();

        return view('proveedores.show', compact('proveedor', 'resumen', 'meses', 'insumos', 'compras', 'ordenes'));
    }

    public function create()
    {
        return view('proveedores.create', ['nombres' => Proveedor::orderBy('nombre')->pluck('nombre'), 'rucs' => Proveedor::whereNotNull('ruc')->pluck('nombre', 'ruc')]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $proveedor = Proveedor::create($datos + ['estado' => $request->input('estado', 'activo') === 'inactivo' ? 'inactivo' : 'activo']);
        return redirect()->route('proveedores.show', $proveedor)->with('success', "Proveedor \"{$proveedor->nombre}\" creado.");
    }

    public function edit(Proveedor $proveedor)
    {
        $proveedor->loadCount(['compras', 'insumos']);
        return view('proveedores.edit', [
            'proveedor' => $proveedor,
            'nombres'   => Proveedor::where('id', '<>', $proveedor->id)->orderBy('nombre')->pluck('nombre'),
            'rucs'      => Proveedor::where('id', '<>', $proveedor->id)->whereNotNull('ruc')->pluck('nombre', 'ruc'),
        ]);
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $datos = $this->validar($request, $proveedor->id);
        if ($request->filled('estado')) $datos['estado'] = $request->estado === 'inactivo' ? 'inactivo' : 'activo';
        $proveedor->update($datos);
        return redirect()->route('proveedores.show', $proveedor)->with('success', 'Proveedor actualizado.');
    }

    private function validar(Request $request, ?int $ignorar = null): array
    {
        // Se guardan sin espacios de sobra; el RUC solo con números
        $request->merge([
            'nombre'   => trim(preg_replace('/\s+/', ' ', (string) $request->nombre)),
            'ruc'      => $request->filled('ruc') ? preg_replace('/\D/', '', $request->ruc) : null,
            'telefono' => $request->filled('telefono') ? trim($request->telefono) : null,
        ]);
        return $request->validate([
            'nombre'    => ['required', 'string', 'max:150', function ($attr, $valor, $fail) use ($ignorar) {
                // sin importar mayúsculas (igual en MySQL y SQLite)
                if (Proveedor::whereRaw('LOWER(nombre) = ?', [mb_strtolower($valor)])->when($ignorar, fn ($q) => $q->where('id', '<>', $ignorar))->exists()) {
                    $fail('Ya hay un proveedor con ese nombre.');
                }
            }],
            'ruc'       => ['nullable', 'digits:11', 'regex:/^(10|15|17|20)/', Rule::unique('proveedores', 'ruc')->ignore($ignorar)],
            'direccion' => 'nullable|string|max:255',
            'telefono'  => ['nullable', 'string', 'max:20', 'regex:/^[\d\s+\-()]{6,20}$/'],
            'email'     => 'nullable|email|max:150',
            'contacto'  => 'nullable|string|max:100',
        ], [
            'ruc.digits'     => 'El RUC tiene 11 números.',
            'ruc.regex'      => 'El RUC empieza con 10, 15, 17 o 20.',
            'ruc.unique'     => 'Ese RUC ya está registrado en otro proveedor.',
            'telefono.regex' => 'Escribe solo números (puede llevar espacios, + o guiones).',
            'email.email'    => 'El correo no es válido.',
        ]);
    }

    public function toggleEstado(Proveedor $proveedor)
    {
        $nuevoEstado = $proveedor->estado === 'activo' ? 'inactivo' : 'activo';
        $proveedor->update(['estado' => $nuevoEstado]);
        return back()->with('success', 'Proveedor ' . ($nuevoEstado === 'activo' ? 'activado' : 'desactivado') . '.');
    }

    public function destroy(Proveedor $proveedor)
    {
        // Medida de seguridad: para eliminar un proveedor primero hay que desactivarlo.
        if ($proveedor->estado === 'activo') {
            return back()->withErrors([
                'error' => "Por seguridad, primero debes desactivar a \"{$proveedor->nombre}\" antes de poder eliminarlo.",
            ]);
        }

        // Aun desactivado, si tiene historial no se puede eliminar sin romper esos registros.
        $usos = [];
        if ($proveedor->compras()->exists()) $usos[] = 'tiene compras registradas';
        if (OrdenAutomatica::where('id_proveedor', $proveedor->id)->exists()) $usos[] = 'tiene órdenes automáticas asociadas';
        if ($proveedor->insumos()->exists()) $usos[] = 'es el proveedor de ' . $proveedor->insumos()->count() . ' insumo(s)';

        if (!empty($usos)) {
            return back()->withErrors([
                'error' => "No se puede eliminar \"{$proveedor->nombre}\" porque " . implode(' y ', $usos) .
                           '. Permanecerá desactivado para conservar el historial.',
            ]);
        }

        $nombre = $proveedor->nombre;
        $proveedor->delete();
        return redirect()->route('proveedores.index')->with('success', "Proveedor \"{$nombre}\" eliminado correctamente.");
    }
}
