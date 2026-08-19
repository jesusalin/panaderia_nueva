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
    public function index()
    {
        $materias = MateriaPrima::with('unidad')
            ->where('estado', 'activo')
            ->orderBy('nombre')->get();

        return view('conteo-fisico.index', compact('materias'));
    }

    /**
     * Guarda el conteo. Solo procesa los insumos a los que se les puso
     * un valor (los que se dejaron vacíos se asume que no se contaron
     * en esta jornada y se ignoran). Si hay diferencia entre lo contado
     * y el stock del sistema, ajusta el stock automáticamente y deja
     * registrado el movimiento, igual que un ajuste manual.
     */
    public function store(Request $request)
    {
        $request->validate([
            'conteo'                 => 'required|array|min:1',
            'conteo.*.stock_fisico'  => 'nullable|numeric|min:0',
            'observacion_general'    => 'nullable|string|max:255',
        ]);

        $items = collect($request->conteo)->filter(fn($item) => $item['stock_fisico'] !== null && $item['stock_fisico'] !== '');

        if ($items->isEmpty()) {
            return back()->with('error', 'No ingresaste ningún valor de conteo físico.');
        }

        $lote = now()->format('YmdHis');
        $ajustados = 0;

        DB::beginTransaction();
        try {
            foreach ($items as $idMateria => $item) {
                $materia = MateriaPrima::findOrFail($idMateria);
                $stockSistema = $materia->stock_actual;
                $stockFisico  = (float) $item['stock_fisico'];
                $diferencia   = $stockFisico - $stockSistema;

                ConteoFisico::create([
                    'lote'          => $lote,
                    'id_materia'    => $materia->id,
                    'id_usuario'    => auth()->id(),
                    'stock_sistema' => $stockSistema,
                    'stock_fisico'  => $stockFisico,
                    'diferencia'    => $diferencia,
                    'observacion'   => $request->observacion_general,
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
                        'observacion'   => "Conteo físico #{$lote} (" . ($diferencia > 0 ? '+' : '') . number_format($diferencia, 3) . ')',
                    ]);
                    $ajustados++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al guardar el conteo: ' . $e->getMessage());
        }

        $mensaje = "Conteo físico registrado: {$items->count()} insumo(s) contado(s)";
        $mensaje .= $ajustados > 0 ? ", {$ajustados} con ajuste de stock." : ', sin diferencias.';

        return redirect()->route('conteo-fisico.historial')->with('success', $mensaje);
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

        // Exactitud promedio y usuario responsable de cada lote
        $lotes->getCollection()->transform(function ($grupo) {
            $items = ConteoFisico::with('usuario')->where('lote', $grupo->lote)->get();
            $grupo->exactitud_promedio = round($items->avg('exactitud'), 1);
            $grupo->usuario = $items->first()->usuario;
            return $grupo;
        });

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

        return view('conteo-fisico.historial', compact('lotes', 'conteos'));
    }

    /**
     * Detalle de un lote específico (qué insumos se contaron y con qué diferencia).
     */
    public function detalleLote(string $lote)
    {
        $items = ConteoFisico::with(['materia.unidad', 'usuario'])
            ->where('lote', $lote)->orderBy('id')->get();

        if ($items->isEmpty()) abort(404);

        return view('conteo-fisico.detalle', compact('items', 'lote'));
    }
}
