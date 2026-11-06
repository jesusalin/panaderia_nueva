<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Support\Volver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClientesController extends Controller
{
    /** Vistas rápidas del listado. */
    public const VISTAS = [
        'todos'       => 'Todos',
        'frecuentes'  => 'Frecuentes',
        'recuperar'   => 'Por recuperar',
        'nuevos'      => 'Nuevos del mes',
        'sin_compras' => 'Sin compras',
        'inactivos'   => 'Inactivos',
    ];
    public const ORDENES = [
        'nombre'  => 'Nombre (A-Z)',
        'compras' => 'Los que más compran',
        'ultima'  => 'Compra más reciente',
        'nuevos'  => 'Registrados recientemente',
    ];

    public function index(Request $request)
    {
        $vista = array_key_exists($request->vista, self::VISTAS) ? $request->vista : 'todos';
        $orden = array_key_exists($request->orden, self::ORDENES) ? $request->orden : 'nombre';
        $hace30 = now()->subDays(Cliente::DIAS_SIN_COMPRAR);
        $inicioMes = now()->startOfMonth();
        $completadas = fn ($q) => $q->where('estado', 'completada');

        $q = Cliente::query()
            ->withCount(['ventas as compras_count' => $completadas])
            ->withCount(['ventas as compras_30' => fn ($v) => $v->where('estado', 'completada')->where('fecha_venta', '>=', $hace30)])
            ->withSum(['ventas as total_comprado' => $completadas], 'total')
            ->withSum(['ventas as total_mes' => fn ($v) => $v->where('estado', 'completada')->where('fecha_venta', '>=', $inicioMes)], 'total')
            ->withMax(['ventas as ultima_compra' => $completadas], 'fecha_venta');

        if ($b = trim((string) $request->buscar)) {
            $digitos = preg_replace('/\D/', '', $b);
            $q->where(function ($w) use ($b, $digitos) {
                $w->where('nombre', 'like', "%$b%")->orWhere('distrito', 'like', "%$b%")->orWhere('contacto', 'like', "%$b%")
                  ->orWhere('email', 'like', "%$b%")->orWhere('direccion', 'like', "%$b%");
                if (strlen($digitos) >= 3) {
                    $w->orWhere('ruc', 'like', "%$digitos%")->orWhere('dni', 'like', "%$digitos%")
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(COALESCE(telefono, ''), ' ', ''), '-', ''), '+', '') LIKE ?", ["%$digitos%"]);
                }
            });
        }
        $q->when($request->tipo && isset(Cliente::TIPOS[$request->tipo]), fn ($w) => $w->where('tipo', $request->tipo));

        match ($vista) {
            'frecuentes'  => $q->activos()->has('ventas', '>=', 4, 'and', fn ($v) => $v->where('estado', 'completada')->where('fecha_venta', '>=', $hace30)),
            'recuperar'   => $q->activos()->where('nombre', '!=', Cliente::GENERAL)->whereHas('ventas', $completadas)
                                ->whereDoesntHave('ventas', fn ($v) => $v->where('estado', 'completada')->where('fecha_venta', '>=', $hace30)),
            'nuevos'      => $q->where('created_at', '>=', $inicioMes),
            'sin_compras' => $q->activos()->whereDoesntHave('ventas', $completadas),
            'inactivos'   => $q->where('estado', 'inactivo'),
            default       => null,
        };

        // El Cliente General (mostrador) siempre primero; los inactivos al final
        $q->orderByRaw('CASE WHEN nombre = ? THEN 0 ELSE 1 END', [Cliente::GENERAL])
          ->orderByRaw("CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END");
        match ($orden) {
            'compras' => $q->orderByDesc('total_comprado'),
            'ultima'  => $q->orderByRaw('ultima_compra IS NULL')->orderByDesc('ultima_compra'),
            'nuevos'  => $q->orderByDesc('created_at'),
            default   => null,
        };
        $clientes = $q->orderBy('nombre')->paginate(18)->withQueryString();

        // Resumen (sin el Cliente General, que son las ventas de mostrador)
        $conNombre = Cliente::where('nombre', '!=', Cliente::GENERAL);
        $ventasMes = Venta::where('estado', 'completada')->where('fecha_venta', '>=', $inicioMes)
            ->whereHas('cliente', fn ($c) => $c->where('nombre', '!=', Cliente::GENERAL));
        $topMes = (clone $ventasMes)->select('id_cliente', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as n'))
            ->groupBy('id_cliente')->orderByDesc('total')->with('cliente')->first();
        $stats = [
            'activos'    => (clone $conNombre)->activos()->count(),
            'inactivos'  => (clone $conNombre)->where('estado', 'inactivo')->count(),
            'compraron'  => (clone $ventasMes)->distinct('id_cliente')->count('id_cliente'),
            'total_mes'  => (float) (clone $ventasMes)->sum('total'),
            'mostrador'  => (float) Venta::where('estado', 'completada')->where('fecha_venta', '>=', $inicioMes)
                                ->where(fn ($v) => $v->whereNull('id_cliente')->orWhereHas('cliente', fn ($c) => $c->where('nombre', Cliente::GENERAL)))->sum('total'),
            'recuperar'  => (clone $conNombre)->activos()->whereHas('ventas', $completadas)
                                ->whereDoesntHave('ventas', fn ($v) => $v->where('estado', 'completada')->where('fecha_venta', '>=', $hace30))->count(),
            'nuevos'     => (clone $conNombre)->where('created_at', '>=', $inicioMes)->count(),
            'top'        => $topMes,
        ];
        $porTipo = Cliente::select('tipo', DB::raw('COUNT(*) as n'))->where('nombre', '!=', Cliente::GENERAL)->groupBy('tipo')->pluck('n', 'tipo');

        return view('clientes.index', compact('clientes', 'stats', 'porTipo', 'vista', 'orden'));
    }

    public function create()
    {
        return view('clientes.create', $this->datosFormulario());
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $cliente = Cliente::create($datos);
        return redirect()->route('clientes.show', $cliente)->with('success', 'Cliente "' . $cliente->nombre . '" registrado.');
    }

    public function show(Request $request, Cliente $cliente)
    {
        $completadas = $cliente->ventas()->where('estado', 'completada');
        $resumen = [
            'total'    => (float) (clone $completadas)->sum('total'),
            'compras'  => (clone $completadas)->count(),
            'primera'  => (clone $completadas)->min('fecha_venta'),
            'ultima'   => (clone $completadas)->max('fecha_venta'),
            'anuladas' => $cliente->ventas()->where('estado', 'anulada')->count(),
            'mes'      => (float) (clone $completadas)->where('fecha_venta', '>=', now()->startOfMonth())->sum('total'),
        ];
        $resumen['promedio'] = $resumen['compras'] ? $resumen['total'] / $resumen['compras'] : 0;
        // Cada cuántos días compra (promedio entre la primera y la última compra)
        $resumen['cada'] = $resumen['compras'] > 1
            ? max(1, (int) round(\Carbon\Carbon::parse($resumen['primera'])->diffInDays(\Carbon\Carbon::parse($resumen['ultima'])) / ($resumen['compras'] - 1)))
            : null;
        $resumen['dias_sin'] = $resumen['ultima'] ? (int) \Carbon\Carbon::parse($resumen['ultima'])->startOfDay()->diffInDays(today()) : null;
        $resumen['recuperar'] = !$cliente->esGeneral() && $resumen['dias_sin'] !== null && $resumen['dias_sin'] >= Cliente::DIAS_SIN_COMPRAR;

        // Lo que más compra
        $productos = VentaDetalle::whereHas('venta', fn ($v) => $v->where('id_cliente', $cliente->id)->where('estado', 'completada'))
            ->select('id_producto', DB::raw('SUM(cantidad) as cantidad'), DB::raw('SUM(subtotal) as total'), DB::raw('COUNT(DISTINCT id_venta) as veces'))
            ->groupBy('id_producto')->orderByDesc('total')->with('producto.categoria')->limit(8)->get();

        // Compras de los últimos 6 meses
        $meses = collect(range(5, 0))->map(function ($i) use ($cliente) {
            $m = now()->startOfMonth()->subMonthsNoOverflow($i);
            $v = $cliente->ventas()->where('estado', 'completada')->whereBetween('fecha_venta', [$m, $m->copy()->endOfMonth()]);
            return ['mes' => $m, 'total' => (float) (clone $v)->sum('total'), 'n' => (clone $v)->count()];
        });

        $medios = (clone $completadas)->select('tipo_pago', DB::raw('COUNT(*) as n'), DB::raw('SUM(total) as total'))
            ->groupBy('tipo_pago')->orderByDesc('n')->get();

        $ventas = $cliente->ventas()->with(['detalles.producto', 'usuario'])->orderByDesc('fecha_venta')->orderByDesc('id')
            ->paginate($request->ajax() ? 5 : 12);

        $datos = compact('cliente', 'resumen', 'productos', 'meses', 'medios', 'ventas');
        return $request->ajax() ? view('clientes._detalle', $datos) : view('clientes.show', $datos);
    }

    public function edit(Cliente $cliente)
    {
        $cliente->loadCount(['ventas as compras_count' => fn ($q) => $q->where('estado', 'completada')]);
        return view('clientes.edit', ['cliente' => $cliente] + $this->datosFormulario($cliente));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $datos = $this->validar($request, $cliente);
        if ($cliente->esGeneral()) {                       // el de mostrador se queda como está
            $datos['nombre'] = Cliente::GENERAL;
            $datos['estado'] = 'activo';
        }
        $cliente->update($datos);
        return redirect()->to(Volver::url(route('clientes.index')))->with('success', 'Cliente "' . $cliente->nombre . '" actualizado.');
    }

    /** Interruptor Activo / Inactivo del listado. */
    public function toggleEstado(Cliente $cliente)
    {
        if ($cliente->esGeneral()) {
            return back()->with('error', 'El Cliente General es el de las ventas de mostrador: no se puede desactivar.');
        }
        $nuevo = $cliente->estado === 'inactivo' ? 'activo' : 'inactivo';
        $cliente->update(['estado' => $nuevo]);
        return back()->with('success', $nuevo === 'activo'
            ? '"' . $cliente->nombre . '" está activo otra vez.'
            : '"' . $cliente->nombre . '" desactivado: ya no aparece para nuevas ventas, pero se conserva su historial.');
    }

    /** Solo se elimina un cliente que nunca compró; si tiene ventas se desactiva para no perder el historial. */
    public function destroy(Cliente $cliente)
    {
        if ($cliente->esGeneral()) {
            return redirect()->route('clientes.index')->with('error', 'No se puede eliminar el Cliente General.');
        }
        if ($n = $cliente->ventas()->count()) {
            return redirect()->route('clientes.index')->with('error', '"' . $cliente->nombre . '" tiene ' . $n . ' venta(s): no se elimina, solo se puede desactivar.');
        }
        $nombre = $cliente->nombre;
        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente "' . $nombre . '" eliminado.');
    }

    // ─────────────────────────────────────────────────────────────────────

    private function datosFormulario(?Cliente $actual = null): array
    {
        $otros = Cliente::when($actual, fn ($q) => $q->where('id', '!=', $actual->id))->get(['id', 'nombre', 'ruc', 'dni']);
        return [
            'nombres'   => $otros->pluck('nombre')->values(),
            'documentos'=> $otros->filter(fn ($c) => $c->ruc || $c->dni)->flatMap(fn ($c) => array_filter([$c->ruc => $c->nombre, $c->dni => $c->nombre]))->all(),
            'distritos' => Cliente::whereNotNull('distrito')->where('distrito', '!=', '')->distinct()->orderBy('distrito')->pluck('distrito'),
        ];
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        // Documentos y teléfono: se guardan sin espacios ni guiones
        $request->merge([
            'ruc' => preg_replace('/\D/', '', (string) $request->ruc) ?: null,
            'dni' => preg_replace('/\D/', '', (string) $request->dni) ?: null,
            'nombre' => trim(preg_replace('/\s+/', ' ', (string) $request->nombre)),
        ]);
        $id = $cliente?->id;
        $datos = $request->validate([
            'nombre'     => ['required', 'string', 'max:150', function ($attr, $valor, $fail) use ($id) {
                $existe = Cliente::whereRaw('LOWER(nombre) = ?', [mb_strtolower($valor)])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();
                if ($existe) $fail('Ya existe un cliente con ese nombre.');
            }],
            'tipo'       => ['required', Rule::in(array_keys(Cliente::TIPOS))],
            'contacto'   => 'nullable|string|max:100',
            'ruc'        => ['nullable', 'digits:11', 'regex:/^(10|15|17|20)/', Rule::unique('clientes', 'ruc')->ignore($id)],
            'dni'        => ['nullable', 'digits:8', Rule::unique('clientes', 'dni')->ignore($id)],
            'telefono'   => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\-+()]{6,20}$/'],
            'email'      => 'nullable|email|max:150',
            'direccion'  => 'nullable|string|max:255',
            'distrito'   => 'nullable|string|max:100',
            'referencia' => 'nullable|string|max:255',
            'notas'      => 'nullable|string|max:300',
            'estado'     => 'nullable|in:activo,inactivo',
        ], [
            'ruc.digits' => 'El RUC tiene 11 números.', 'ruc.regex' => 'Un RUC empieza con 10, 15, 17 o 20.', 'ruc.unique' => 'Ese RUC ya es de otro cliente.',
            'dni.digits' => 'El DNI tiene 8 números.', 'dni.unique' => 'Ese DNI ya es de otro cliente.',
            'telefono.regex' => 'Escribe solo números (puede llevar espacios o guiones).',
        ]);
        $datos['estado'] = $datos['estado'] ?? 'activo';
        $datos['distrito'] = isset($datos['distrito']) ? (mb_convert_case(trim($datos['distrito']), MB_CASE_TITLE) ?: null) : null;
        return $datos;
    }
}
