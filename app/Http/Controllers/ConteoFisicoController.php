<?php
namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use App\Models\ConteoFisico;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConteoFisicoController extends Controller
{
    /**
     * Formulario para hacer un nuevo conteo físico: lista todos los
     * insumos activos con su stock actual (según el sistema) y un
     * campo para anotar lo que se contó a mano.
     */
    public function index(Request $request)
    {
        $hayControl = MateriaPrima::where('estado', 'activo')->where('es_control', true)->exists();
        $modo = $request->input('modo', $hayControl ? 'control' : 'todos');
        if ($modo === 'control' && !$hayControl) $modo = 'todos';

        $materias = MateriaPrima::with('unidad')->where('estado', 'activo')
            ->when($modo === 'control', fn ($q) => $q->where('es_control', true))
            ->orderBy('nombre')->get();

        // PER del último conteo, para comparar
        $ultimo = ConteoFisico::select('lote')->selectRaw('MIN(created_at) as fecha, COUNT(*) as verificados, SUM(CASE WHEN diferencia <> 0 THEN 1 ELSE 0 END) as con_diferencia')
            ->groupBy('lote')->orderByDesc('fecha')->first();
        if ($ultimo) $ultimo->per = $ultimo->verificados ? round($ultimo->con_diferencia / $ultimo->verificados * 100, 1) : 0;

        return view('conteo-fisico.index', compact('materias', 'modo', 'hayControl', 'ultimo'));
    }

    /**
     * Guarda el conteo. Solo procesa los insumos a los que se les puso un valor.
     * Si lo contado no coincide con el sistema, se corrige el stock y queda el
     * movimiento de ajuste en el kardex. Luego muestra el resultado con su PER.
     */
    public function store(Request $request)
    {
        $request->validate([
            'conteo'                 => 'required|array|min:1',
            'conteo.*.stock_fisico'  => 'nullable|numeric|min:0',
            'conteo.*.unidad'        => 'nullable|in:base,sub',
            'observacion_general'    => 'nullable|string|max:200',
            'modo'                   => 'nullable|in:control,todos',
        ]);

        $items = collect($request->conteo)->filter(fn($item) => isset($item['stock_fisico']) && $item['stock_fisico'] !== null && $item['stock_fisico'] !== '');

        if ($items->isEmpty()) {
            return back()->with('error', 'No ingresaste ningún valor de conteo físico.');
        }

        $lote = now()->format('YmdHis');
        $tipoConteo = $request->modo === 'control' ? 'Conteo de insumos de control' : 'Conteo físico';
        $observacion = trim($tipoConteo . ($request->observacion_general ? ' — ' . $request->observacion_general : ''));
        $ajustados = 0;

        DB::beginTransaction();
        try {
            foreach ($items as $idMateria => $item) {
                $materia = MateriaPrima::with('unidad')->lockForUpdate()->findOrFail($idMateria);
                $factor = (($item['unidad'] ?? 'base') === 'sub' && ProduccionController::subunidad($materia->unidad->abreviatura ?? '')) ? 1000 : 1;
                $stockSistema = (float) $materia->stock_actual;
                $stockFisico  = round((float) $item['stock_fisico'] / $factor, 3);
                $diferencia   = round($stockFisico - $stockSistema, 3);

                ConteoFisico::create([
                    'lote'          => $lote,
                    'id_materia'    => $materia->id,
                    'id_usuario'    => auth()->id(),
                    'stock_sistema' => $stockSistema,
                    'stock_fisico'  => $stockFisico,
                    'diferencia'    => $diferencia,
                    'observacion'   => mb_substr($observacion, 0, 255),
                ]);

                if ($diferencia != 0) {
                    $materia->update(['stock_actual' => $stockFisico]);

                    MovimientoInventario::create([
                        'id_materia'    => $materia->id,
                        'id_usuario'    => auth()->id(),
                        'tipo'          => 'ajuste',
                        'motivo'        => 'ajuste_manual',
                        'referencia_id' => null,
                        'cantidad'      => abs($diferencia),
                        'stock_antes'   => $stockSistema,
                        'stock_despues' => $stockFisico,
                        'observacion'   => "Conteo físico #{$lote} (" . \App\Support\Cantidad::conUnidad($diferencia, $materia->unidad->abreviatura ?? '', true) . ')',
                        'created_at'    => now(),
                    ]);
                    $ajustados++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Ocurrió un error al guardar el conteo: ' . $e->getMessage());
        }

        $per = round($ajustados / $items->count() * 100, 1);
        $mensaje = "Conteo registrado: {$items->count()} insumo(s) contado(s), "
            . ($ajustados ? "{$ajustados} con diferencia (stock corregido)." : 'todos coinciden con el sistema.')
            . " PER de este conteo: {$per} %.";

        return redirect()->route('conteo-fisico.detalle', $lote)->with('success', $mensaje);
    }

    /**
     * Historial de sesiones de conteo, agrupadas por lote, con la
     * exactitud promedio de cada una. Soporta filtro por pestañas y
     * respuesta parcial por AJAX (igual que Órdenes Automáticas).
     */
    public function historial(Request $request)
    {
        $query = ConteoFisico::select('lote')
            ->selectRaw('MIN(created_at) as fecha')
            ->selectRaw('COUNT(*) as total_items')
            ->selectRaw('SUM(CASE WHEN diferencia != 0 THEN 1 ELSE 0 END) as total_ajustados')
            ->groupBy('lote');

        if ($request->filtro === 'con_ajustes') {
            $query->havingRaw('total_ajustados > 0');
        } elseif ($request->filtro === 'sin_diferencias') {
            $query->havingRaw('total_ajustados = 0');
        }

        $lotes = $query->orderByDesc('fecha')->paginate(10)->withQueryString();

        // PER (fórmula de la tesis), exactitud promedio, valor de las diferencias y responsable de cada lote
        $lotes->getCollection()->transform(function ($grupo) {
            $items = ConteoFisico::with(['usuario', 'materia'])->where('lote', $grupo->lote)->get();
            $grupo->per = $grupo->total_items ? round($grupo->total_ajustados / $grupo->total_items * 100, 1) : 0;
            $grupo->exactitud_promedio = round($items->avg('exactitud'), 1);
            $grupo->valor_diferencias = round($items->sum(fn ($i) => $i->diferencia * ($i->materia->costo_unitario ?? 0)), 2);
            $grupo->tipo = str_contains((string) $items->first()->observacion, 'control') ? 'Control (PER)' : 'Completo';
            $grupo->usuario = $items->first()->usuario;
            return $grupo;
        });

        // Tendencia del PER (últimos 10 conteos, del más antiguo al más reciente)
        $tendencia = ConteoFisico::select('lote')->selectRaw('MIN(created_at) as fecha, COUNT(*) as verificados, SUM(CASE WHEN diferencia <> 0 THEN 1 ELSE 0 END) as con_diferencia')
            ->groupBy('lote')->orderByDesc('fecha')->take(10)->get()
            ->map(fn ($c) => ['fecha' => \Carbon\Carbon::parse($c->fecha), 'per' => $c->verificados ? round($c->con_diferencia / $c->verificados * 100, 1) : 0])
            ->reverse()->values();

        // Conteos para las pestañas (siempre sobre el total, sin filtrar)
        $todosLosLotes = ConteoFisico::select('lote')
            ->selectRaw('SUM(CASE WHEN diferencia != 0 THEN 1 ELSE 0 END) as total_ajustados')
            ->groupBy('lote')->get();

        $conteos = [
            'todas'            => $todosLosLotes->count(),
            'con_ajustes'      => $todosLosLotes->where('total_ajustados', '>', 0)->count(),
            'sin_diferencias'  => $todosLosLotes->where('total_ajustados', 0)->count(),
        ];

        if ($request->ajax()) {
            return view('conteo-fisico._lista-historial', compact('lotes'));
        }

        return view('conteo-fisico.historial', compact('lotes', 'conteos', 'tendencia'));
    }

    /**
     * Detalle de un lote específico (qué insumos se contaron y con qué diferencia).
     */
    public function detalleLote(string $lote)
    {
        $items = ConteoFisico::with(['materia.unidad', 'usuario'])
            ->where('lote', $lote)->orderBy('id')->get();

        if ($items->isEmpty()) abort(404);

        $resumen = [
            'verificados'    => $items->count(),
            'con_diferencia' => $items->where('diferencia', '!=', 0)->count(),
            'faltante'       => round($items->where('diferencia', '<', 0)->sum(fn ($i) => abs($i->diferencia) * ($i->materia->costo_unitario ?? 0)), 2),
            'sobrante'       => round($items->where('diferencia', '>', 0)->sum(fn ($i) => $i->diferencia * ($i->materia->costo_unitario ?? 0)), 2),
        ];
        $resumen['per'] = $resumen['verificados'] ? round($resumen['con_diferencia'] / $resumen['verificados'] * 100, 1) : 0;

        // Ordena: primero los que no coincidieron (de mayor a menor valor de la diferencia)
        $items = $items->sortByDesc(fn ($i) => [$i->diferencia != 0, abs($i->diferencia) * ($i->materia->costo_unitario ?? 0)])->values();

        return view('conteo-fisico.detalle', compact('items', 'lote', 'resumen'));
    }
}
