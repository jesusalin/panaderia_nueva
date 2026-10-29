<?php
namespace App\Http\Controllers;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\KardexProducto;
use App\Models\VentaDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductosController extends Controller
{
    public function index(Request $request) {
        $request->validate([
            'buscar'    => 'nullable|string|max:100',
            'categoria' => 'nullable|integer',
            'estado'    => 'nullable|in:activo,inactivo',
            'stock'     => 'nullable|in:bajo,agotado,ok',
            'orden'     => 'nullable|in:nombre,precio_asc,precio_desc,stock,vendidos,margen',
        ]);

        $desde30 = now()->subDays(30);
        $productos = Producto::with('categoria')
            ->withCount(['ventaDetalles', 'producciones', 'receta', 'kardex'])
            ->withSum(['ventaDetalles as vendidos_30' => fn ($q) => $q->whereHas('venta',
                fn ($v) => $v->where('estado', 'completada')->where('fecha_venta', '>=', $desde30))], 'cantidad')
            ->when($request->buscar, fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', '%' . $request->buscar . '%')
                ->orWhere('descripcion', 'like', '%' . $request->buscar . '%')))
            ->when($request->categoria, fn ($q) => $q->where('id_categoria', $request->categoria))
            ->when($request->estado, fn ($q) => $q->where('estado', $request->estado))
            ->when($request->stock === 'agotado', fn ($q) => $q->where('stock_actual', '<=', 0))
            ->when($request->stock === 'bajo', fn ($q) => $q->where('stock_actual', '>', 0)->whereColumn('stock_actual', '<=', 'stock_minimo'))
            ->when($request->stock === 'ok', fn ($q) => $q->whereColumn('stock_actual', '>', 'stock_minimo'));

        match ($request->orden) {
            'precio_asc'  => $productos->orderBy('precio_venta'),
            'precio_desc' => $productos->orderByDesc('precio_venta'),
            'stock'       => $productos->orderByRaw('stock_actual - stock_minimo'),   // los más urgentes primero
            'vendidos'    => $productos->orderByDesc('vendidos_30'),
            'margen'      => $productos->orderByRaw('CASE WHEN costo_produccion > 0 AND precio_venta > 0 THEN (precio_venta - costo_produccion) / precio_venta ELSE -1 END DESC'),
            default       => null,
        };
        $productos = $productos->orderBy('nombre')->paginate(12)->withQueryString();

        $stats = [
            'total'    => Producto::count(),
            'activos'  => Producto::where('estado', 'activo')->count(),
            'bajo'     => Producto::where('estado', 'activo')->where('stock_actual', '>', 0)->whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
            'agotados' => Producto::where('estado', 'activo')->where('stock_actual', '<=', 0)->count(),
        ];

        $categorias = Categoria::orderBy('nombre')->get();

        // Solo se necesita si el usuario puede vender (para el carrito de compra rápida)
        $clientes = auth()->user()->hasModulo('ventas') ? Cliente::orderBy('nombre')->get() : collect();

        return view('productos.index', compact('productos', 'categorias', 'clientes', 'stats'));
    }

    /** Ficha del producto: datos, ganancia, receta con su costo, ventas y últimos movimientos. */
    public function show(Producto $producto) {
        $producto->load(['categoria', 'receta.detalles.materia.unidad']);

        $ventas = VentaDetalle::where('id_producto', $producto->id)
            ->whereHas('venta', fn ($v) => $v->where('estado', 'completada')->where('fecha_venta', '>=', now()->subDays(29)->startOfDay()))
            ->with('venta:id,fecha_venta')->get(['id_venta', 'cantidad', 'subtotal']);

        // Unidades vendidas por día (últimos 14 días, con ceros)
        $porDia = $ventas->groupBy(fn ($d) => $d->venta->fecha_venta->toDateString())->map->sum('cantidad');
        $dias = collect(range(13, 0))->map(fn ($n) => now()->subDays($n)->startOfDay())
            ->map(fn ($f) => ['fecha' => $f, 'cantidad' => (int) ($porDia[$f->toDateString()] ?? 0)]);

        $resumen = [
            'vendidos30' => (int) $ventas->sum('cantidad'),
            'ingresos30' => (float) $ventas->sum('subtotal'),
            'promedioDia'=> round($ventas->sum('cantidad') / 30, 1),
            'producido30'=> (int) $producto->producciones()->where('created_at', '>=', now()->subDays(30))->sum('cantidad'),
        ];
        $resumen['diasCobertura'] = $resumen['promedioDia'] > 0 ? floor($producto->stock_actual / $resumen['promedioDia']) : null;

        $movimientos = KardexProducto::with('usuario:id,nombre')->where('id_producto', $producto->id)
            ->orderByDesc('created_at')->orderByDesc('id')->limit(10)->get();

        $costoReceta = $producto->costoSegunReceta();

        return view('productos.show', compact('producto', 'dias', 'resumen', 'movimientos', 'costoReceta'));
    }

    /** Formulario para ajustar el stock (merma o corrección por conteo) dejando rastro en el kardex. */
    public function ajusteForm(Producto $producto) {
        return view('productos.ajuste', compact('producto'));
    }

    public function ajusteStore(Request $request, Producto $producto) {
        $request->validate([
            'tipo_ajuste' => 'required|in:merma,conteo',
            'cantidad'    => 'required_if:tipo_ajuste,merma|nullable|integer|min:1',
            'stock_real'  => 'required_if:tipo_ajuste,conteo|nullable|integer|min:0',
            'observacion' => 'required|string|max:200',
        ], [
            'cantidad.required_if'   => 'Indica cuántas unidades se perdieron.',
            'stock_real.required_if' => 'Indica cuántas unidades contaste.',
            'observacion.required'   => 'Escribe el motivo (ej. "se quemó una bandeja").',
        ]);

        $resultado = DB::transaction(function () use ($request, $producto) {
            $p = Producto::lockForUpdate()->findOrFail($producto->id);
            $antes = (int) $p->stock_actual;
            $despues = $request->tipo_ajuste === 'merma' ? $antes - (int) $request->cantidad : (int) $request->stock_real;

            if ($despues < 0) return ['error' => "Solo hay {$antes} unidad(es); no puedes descontar {$request->cantidad}."];
            if ($despues === $antes) return ['error' => 'El stock contado es igual al del sistema: no hay nada que ajustar.'];

            $p->update(['stock_actual' => $despues]);
            KardexProducto::create([
                'id_producto'   => $p->id,
                'id_usuario'    => auth()->id(),
                'tipo'          => $despues > $antes ? 'entrada' : 'salida',
                'motivo'        => 'ajuste_manual',
                'referencia_id' => null,
                'cantidad'      => abs($despues - $antes),
                'stock_antes'   => $antes,
                'stock_despues' => $despues,
                'observacion'   => ($request->tipo_ajuste === 'merma' ? 'Merma: ' : 'Corrección por conteo: ') . $request->observacion,
                'created_at'    => now(),   // hora de la app (America/Lima), no la del servidor de BD
            ]);
            return ['antes' => $antes, 'despues' => $despues];
        });

        if (isset($resultado['error'])) {
            return back()->withInput()->with('error', $resultado['error']);
        }
        return redirect()->route('productos.show', $producto)
            ->with('success', "Stock de \"{$producto->nombre}\" ajustado: {$resultado['antes']} → {$resultado['despues']} uds (quedó en el kardex).");
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

        DB::transaction(function () use ($data) {
            $producto = Producto::create($data);
            // El stock con el que nace el producto queda registrado en el kardex
            if ($producto->stock_actual > 0) {
                KardexProducto::create([
                    'id_producto' => $producto->id, 'id_usuario' => auth()->id(),
                    'tipo' => 'entrada', 'motivo' => 'ajuste_manual', 'referencia_id' => null,
                    'cantidad' => $producto->stock_actual, 'stock_antes' => 0, 'stock_despues' => $producto->stock_actual,
                    'observacion' => 'Stock inicial al registrar el producto', 'created_at' => now(),
                ]);
            }
        });
        return redirect()->route('productos.index')->with('success','Producto creado correctamente.');
    }

    public function edit(Producto $producto) {
        $categorias = Categoria::where('estado','activo')->orderBy('nombre')->get();
        $costoReceta = $producto->costoSegunReceta();
        return view('productos.edit', compact('producto','categorias','costoReceta'));
    }

    public function update(Request $request, Producto $producto) {
        $data = $request->validate([
            'id_categoria'=>'required|exists:categorias,id',
            'nombre'=>'required|string|max:150',
            'descripcion'=>'nullable|string',
            'precio_venta'=>'required|numeric|min:0',
            'costo_produccion'=>'nullable|numeric|min:0',
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

        // El stock NO se cambia aquí (rompería el kardex): se usa "Ajustar stock"
        $producto->update($data);
        return redirect()->route('productos.index')->with('success','Producto actualizado.');
    }

    public function toggleEstado(Producto $producto) {
        $nuevoEstado = $producto->estado === 'activo' ? 'inactivo' : 'activo';
        $producto->update(['estado' => $nuevoEstado]);

        return back()->with('success', 'Producto ' . ($nuevoEstado === 'activo' ? 'activado' : 'desactivado') . '.');
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
