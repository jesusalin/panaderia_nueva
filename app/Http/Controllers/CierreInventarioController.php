<?php

namespace App\Http\Controllers;

use App\Exports\TablaExport;
use App\Models\CierreInventario;
use App\Models\MovimientoInventario;
use App\Support\CierreDiario;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Cierre diario de inventario (indicador TCI de la tesis):
 * todos los insumos con saldo inicial, entradas, salidas, ajustes y saldo final del día,
 * y el botón para dejar registrado el cierre (quién y a qué hora).
 */
class CierreInventarioController extends Controller
{
    public const FILTROS = ['todos' => 'Todos los insumos', 'movimiento' => 'Solo con movimiento', 'bajo' => 'Bajo el mínimo', 'descuadre' => 'No cuadran'];

    private function dia(Request $request): Carbon
    {
        $request->validate(['fecha' => 'nullable|date|before_or_equal:today'], ['fecha.before_or_equal' => 'No se puede cerrar un día que todavía no llega.']);
        return $request->filled('fecha') ? Carbon::parse($request->fecha)->startOfDay() : today();
    }

    private function datos(Request $request): array
    {
        $dia = $this->dia($request);
        $filas = CierreDiario::calcular($dia);
        $totales = CierreDiario::totales($filas);
        $cierre = CierreInventario::with('usuario')->whereDate('fecha', $dia)->first();

        // ¿Cambió algo después de registrar el cierre? (movimientos con fecha de ese día cargados más tarde)
        $cambio = false;
        if ($cierre) {
            $foto = collect($cierre->detalle)->keyBy('id');
            $cambio = $filas->contains(fn ($f) => !isset($foto[$f['id']]) || abs($foto[$f['id']]['final'] - $f['final']) > 0.0005);
        }

        $filtro = $request->input('ver', 'todos');
        if (!isset(self::FILTROS[$filtro])) $filtro = 'todos';
        $vista = $filas->filter(fn ($f) => match ($filtro) {
            'movimiento' => $f['movimientos'] > 0,
            'bajo'       => $f['bajo'],
            'descuadre'  => !$f['cuadra'],
            default      => true,
        });
        if ($request->filled('buscar')) {
            $b = mb_strtolower($request->buscar);
            $vista = $vista->filter(fn ($f) => str_contains(mb_strtolower($f['nombre']), $b));
        }

        return compact('dia', 'filas', 'vista', 'totales', 'cierre', 'cambio', 'filtro');
    }

    public function index(Request $request)
    {
        $datos = $this->datos($request);

        // Últimos 14 días: cuáles se cerraron y cuántos movimientos tuvieron
        $desde = today()->subDays(13);
        $cierres = CierreInventario::with('usuario')->whereDate('fecha', '>=', $desde)->get()->keyBy(fn ($c) => $c->fecha->toDateString());
        $movsPorDia = MovimientoInventario::where('created_at', '>=', $desde)->get(['created_at'])
            ->groupBy(fn ($m) => $m->created_at->toDateString())->map->count();
        $historial = collect(range(0, 13))->map(fn ($i) => today()->subDays($i))->map(fn ($d) => [
            'dia' => $d, 'cierre' => $cierres[$d->toDateString()] ?? null, 'movimientos' => $movsPorDia[$d->toDateString()] ?? 0,
        ]);

        return view('reportes.cierre', $datos + compact('historial'));
    }

    public function guardar(Request $request)
    {
        $dia = $this->dia($request);
        $request->validate(['observacion' => 'nullable|string|max:300']);
        $filas = CierreDiario::calcular($dia);
        $t = CierreDiario::totales($filas);

        $existia = CierreInventario::whereDate('fecha', $dia)->first();
        $datos = [
            'id_usuario'     => auth()->id(),
            'insumos'        => $t['insumos'],
            'con_movimiento' => $t['con_movimiento'],
            'valor_entradas' => $t['valor_entradas'],
            'valor_salidas'  => $t['valor_salidas'],
            'valor_final'    => $t['valor_final'],
            'bajo_minimo'    => $t['bajo_minimo'],
            'observacion'    => $request->observacion,
            'detalle'        => $filas->map(fn ($f) => collect($f)->only(['id', 'nombre', 'unidad', 'inicial', 'entradas', 'salidas', 'ajuste', 'final', 'costo', 'valor_final', 'movimientos'])->all())->all(),
        ];
        $existia ? $existia->update($datos) : CierreInventario::create($datos + ['fecha' => $dia->toDateString()]);

        return redirect()->route('reportes.cierre', ['fecha' => $dia->toDateString()])->with('success',
            ($existia ? 'Cierre actualizado' : 'Cierre registrado') . ' para el ' . $dia->locale('es')->translatedFormat('l d \d\e F')
            . ": {$t['insumos']} insumos, valor final S/ " . number_format($t['valor_final'], 2) . '.'
            . ($t['no_cuadran'] ? " Ojo: {$t['no_cuadran']} insumo(s) no cuadran." : ''));
    }

    public function pdf(Request $request)
    {
        $datos = $this->datos($request);
        $nombre = 'cierre-inventario-' . $datos['dia']->format('Y-m-d') . '.pdf';
        return Pdf::loadView('reportes.cierre-pdf', $datos)->setPaper('a4', 'landscape')->stream($nombre);
    }

    public function excel(Request $request)
    {
        $d = $this->datos($request);
        $filas = $d['vista']->map(fn ($f) => [
            $f['nombre'], $f['unidad'], $f['inicial'], $f['entradas'], $f['salidas'], $f['ajuste'], $f['final'],
            round($f['costo'], 2), $f['valor_final'], $f['movimientos'], $f['bajo'] ? 'Sí' : '', $f['cuadra'] ? 'Sí' : 'NO',
        ])->values()->all();
        $t = $d['totales'];
        $filas[] = [];
        $filas[] = ['TOTAL', '', '', '', '', '', '', '', $t['valor_final'], $t['movimientos'], $t['bajo_minimo'], ''];
        $titulo = 'Cierre de inventario del ' . $d['dia']->format('d/m/Y')
            . ($d['cierre'] ? ' — cerrado por ' . ($d['cierre']->usuario->nombre ?? '') . ' a las ' . $d['cierre']->updated_at->format('H:i') : ' — sin registrar');

        return Excel::download(new TablaExport('Cierre ' . $d['dia']->format('d-m-Y'),
            ['Insumo', 'Unidad', 'Saldo inicial', 'Entradas', 'Salidas', 'Ajustes', 'Saldo final', 'Costo unit. (S/)', 'Valor final (S/)', 'Movimientos', 'Bajo mínimo', 'Cuadra'],
            $filas, $titulo), 'cierre-inventario-' . $d['dia']->format('Y-m-d') . '.xlsx');
    }
}
