<?php

namespace App\Http\Controllers;

use App\Models\TiempoOperacion;
use App\Models\TiempoBaseline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class TiemposOperacionController extends Controller
{
    /**
     * Recibe el tiempo cronometrado en el navegador (JS) y lo guarda.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_operacion' => 'required|in:busqueda_producto,verificacion_stock,registro_venta',
            'duracion_ms'    => 'required|integer|min:1|max:600000', // máx 10 min, descarta datos atípicos
            'referencia_id'  => 'nullable|integer',
        ]);

        TiempoOperacion::create([
            'id_usuario'     => auth()->id(),
            'tipo_operacion' => $data['tipo_operacion'],
            'duracion_ms'    => $data['duracion_ms'],
            'referencia_id'  => $data['referencia_id'] ?? null,
        ]);

        return response()->json(['ok' => true], 201);
    }

    /**
     * Reporte comparativo: tiempo muerto inicial (manual, pre-test) vs
     * tiempo muerto final (promedio registrado por el sistema, post-test).
     * Fórmula de la tesis: (inicial - final) / final x 100
     */
    public function index(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $resultados = $this->calcularResultados($fechaDesde, $fechaHasta);

        return view('tiempos-operacion.index', [
            'resultados'  => $resultados,
            'fechaDesde'  => $fechaDesde,
            'fechaHasta'  => $fechaHasta,
        ]);
    }

    /**
     * Exporta el mismo reporte comparativo a PDF: evidencia lista para
     * anexar en la sustentación (indicador OE3, pre-test vs post-test).
     */
    public function exportarPdf(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $resultados = $this->calcularResultados($fechaDesde, $fechaHasta);

        $pdf = Pdf::loadView('tiempos-operacion.pdf', compact('resultados', 'fechaDesde', 'fechaHasta'))->setPaper('a4');

        return $pdf->stream('reporte-tiempos-operacion.pdf');
    }

    /**
     * Calcula el comparativo inicial (manual, pre-test) vs final (promedio
     * registrado por el sistema, post-test) por tipo de operación.
     * Fórmula de la tesis: (inicial - final) / final x 100
     */
    private function calcularResultados(?string $fechaDesde, ?string $fechaHasta): array
    {
        $query = TiempoOperacion::query();
        if ($fechaDesde) $query->whereDate('created_at', '>=', $fechaDesde);
        if ($fechaHasta) $query->whereDate('created_at', '<=', $fechaHasta);

        $promedios = $query->select(
                'tipo_operacion',
                DB::raw('AVG(duracion_ms) as promedio_ms'),
                DB::raw('COUNT(*) as total_registros')
            )
            ->groupBy('tipo_operacion')
            ->get()
            ->keyBy('tipo_operacion');

        $baselines = TiempoBaseline::all()->keyBy('tipo_operacion');

        $resultados = [];
        foreach (TiempoOperacion::TIPOS as $tipo => $etiqueta) {
            $finalSeg   = isset($promedios[$tipo]) ? round($promedios[$tipo]->promedio_ms / 1000, 2) : null;
            $totalRegs  = $promedios[$tipo]->total_registros ?? 0;
            $inicialSeg = isset($baselines[$tipo]) ? (float) $baselines[$tipo]->segundos_manual : 0;

            $reduccion = null;
            if ($finalSeg && $finalSeg > 0 && $inicialSeg > 0) {
                $reduccion = round((($inicialSeg - $finalSeg) / $finalSeg) * 100, 2);
            }

            $resultados[$tipo] = [
                'etiqueta'   => $etiqueta,
                'inicial'    => $inicialSeg,
                'final'      => $finalSeg,
                'reduccion'  => $reduccion,
                'registros'  => $totalRegs,
            ];
        }

        return $resultados;
    }

    /**
     * Actualiza el tiempo muerto inicial (pre-test manual) por tipo de operación.
     */
    public function actualizarBaseline(Request $request)
    {
        $data = $request->validate([
            'baseline' => 'required|array',
            'baseline.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($data['baseline'] as $tipo => $segundos) {
            if (!array_key_exists($tipo, TiempoOperacion::TIPOS)) continue;
            TiempoBaseline::where('tipo_operacion', $tipo)
                ->update(['segundos_manual' => $segundos ?? 0, 'updated_at' => now()]);
        }

        return back()->with('success', 'Tiempo muerto inicial (pre-test) actualizado correctamente.');
    }
}
