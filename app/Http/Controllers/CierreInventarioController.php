<?php

namespace App\Http\Controllers;

use App\Exports\TablaExport;
use App\Models\CierreInventario;
use App\Models\Compra;
use App\Models\KardexProducto;
use App\Models\Produccion;
use App\Models\Venta;
use App\Support\TablaFiltro;
use App\Models\MovimientoInventario;
use App\Support\CierreDiario;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Cierre diario de inventario (indicador TCI de la tesis):
 * todos los insumos (y los productos terminados) con saldo inicial, entradas, salidas, ajustes y
 * saldo final del día, lo que pasó en el día, y el botón para dejar registrado el cierre
 * (quién, a qué hora y cuánto tardó: indicador TCI).
 */
class CierreInventarioController extends Controller
{
    public const FILTROS = ['todos' => 'Todos los insumos', 'movimiento' => 'Solo con movimiento', 'bajo' => 'Bajo el mínimo', 'descuadre' => 'No cuadran'];

    private function dia(Request $request): Carbon
    {
        $request->validate(['fecha' => 'nullable|date|before_or_equal:today'], ['fecha.before_or_equal' => 'No se puede cerrar un día que todavía no llega.']);
        return $request->filled('fecha') ? Carbon::parse($request->fecha)->startOfDay() : today();
    }

    /** Columnas de la tabla (claves iguales a data-clave en la vista), para ordenar y filtrar el PDF/Excel. */
    private static function columnas(): array
    {
        return [
            'nombre'   => ['titulo' => 'Ítem',          'valor' => fn ($f) => $f['nombre']],
            'inicial'  => ['titulo' => 'Saldo inicial', 'valor' => fn ($f) => $f['inicial'],  'texto' => fn ($f) => ''],
            'entradas' => ['titulo' => 'Entradas',      'valor' => fn ($f) => $f['entradas'], 'texto' => fn ($f) => ''],
            'salidas'  => ['titulo' => 'Salidas',       'valor' => fn ($f) => $f['salidas'],  'texto' => fn ($f) => ''],
            'ajuste'   => ['titulo' => 'Ajustes',       'valor' => fn ($f) => $f['ajuste'],   'texto' => fn ($f) => ''],
            'final'    => ['titulo' => 'Saldo final',   'valor' => fn ($f) => $f['final'],    'texto' => fn ($f) => ''],
            'valor'    => ['titulo' => 'Valor',         'valor' => fn ($f) => $f['valor_final'], 'texto' => fn ($f) => ''],
            'estado'   => ['titulo' => 'Estado',        'valor' => fn ($f) => $f['estado']],
        ];
    }

    private function datos(Request $request): array
    {
        $dia = $this->dia($request);
        $de = $request->input('de') === 'productos' ? 'productos' : 'insumos';
        $filas = CierreDiario::calcular($dia);
        $totales = CierreDiario::totales($filas);
        $filasProd = CierreDiario::calcularProductos($dia);
        $totalesProd = CierreDiario::totales($filasProd);
        $cierre = CierreInventario::with('usuario')->whereDate('fecha', $dia)->first();

        // ¿Cambió algo después de registrar el cierre? (movimientos con fecha de ese día cargados más tarde)
        $cambio = false;
        if ($cierre) {
            $distinto = function ($foto, $actuales) {
                $foto = collect($foto)->keyBy('id');
                return $actuales->contains(fn ($f) => !isset($foto[$f['id']]) || abs($foto[$f['id']]['final'] - $f['final']) > 0.0005);
            };
            $cambio = $distinto($cierre->detalle, $filas)
                || ($cierre->detalle_productos !== null && $distinto($cierre->detalle_productos, $filasProd));
        }

        $filtro = $request->input('ver', 'todos');
        if (!isset(self::FILTROS[$filtro])) $filtro = 'todos';
        $vista = ($de === 'productos' ? $filasProd : $filas)->filter(fn ($f) => match ($filtro) {
            'movimiento' => $f['movimientos'] > 0,
            'bajo'       => $f['bajo'],
            'descuadre'  => !$f['cuadra'],
            default      => true,
        });
        if ($request->filled('buscar')) {
            $b = mb_strtolower($request->buscar);
            $vista = $vista->filter(fn ($f) => str_contains(mb_strtolower($f['nombre']), $b));
        }
        // Orden y filtros que el usuario eligió en la tabla (para que el PDF/Excel salgan igual)
        $vista = TablaFiltro::aplicar($vista->values(), $request, self::columnas());
        $filtroTabla = TablaFiltro::descripcion($request, self::columnas());

        // Lo que pasó en el día (de dónde vienen las entradas y salidas)
        $inicio = $dia->copy()->startOfDay(); $fin = $dia->copy()->endOfDay();
        $comprasDia = Compra::where('estado', 'recibida')->whereBetween('recibida_at', [$inicio, $fin]);
        $ventasDia = Venta::where('estado', 'completada')->whereBetween('fecha_venta', [$inicio, $fin]);
        $mermaInsumos = MovimientoInventario::with('materia')->where('motivo', 'merma')->whereBetween('created_at', [$inicio, $fin])->get()
            ->sum(fn ($m) => $m->cantidad * (float) ($m->materia->costo_unitario ?? 0));
        $mermaProductos = KardexProducto::with('producto')->where('tipo', 'salida')->where('motivo', 'ajuste_manual')
            ->where('observacion', 'like', 'Merma%')->whereBetween('created_at', [$inicio, $fin])->get()
            ->sum(fn ($k) => $k->cantidad * (float) ($k->producto->costo_produccion ?? 0));
        $resumenDia = [
            'compras'        => (clone $comprasDia)->count(),
            'compras_total'  => (float) (clone $comprasDia)->sum('total'),
            'producciones'   => Produccion::whereDate('fecha', $dia)->count(),
            'producido'      => (float) Produccion::whereDate('fecha', $dia)->sum('cantidad'),
            'ventas'         => (clone $ventasDia)->count(),
            'ventas_total'   => (float) (clone $ventasDia)->sum('total'),
            'merma'          => round($mermaInsumos + $mermaProductos, 2),
        ];

        // Valor del almacén el día anterior (del cierre guardado o calculado), para comparar
        $cierreAyer = CierreInventario::whereDate('fecha', $dia->copy()->subDay())->first();
        $valorAyer = $cierreAyer ? (float) $cierreAyer->valor_final : CierreDiario::totales(CierreDiario::calcular($dia->copy()->subDay()))['valor_final'];

        return compact('dia', 'de', 'filas', 'filasProd', 'vista', 'totales', 'totalesProd', 'cierre', 'cambio', 'filtro', 'filtroTabla', 'resumenDia', 'valorAyer');
    }

    public function index(Request $request)
    {
        $datos = $this->datos($request);

        // Últimos 14 días: cuáles se cerraron, cuánto tardó y cuántos movimientos tuvieron
        $desde = today()->subDays(13);
        $cierres = CierreInventario::with('usuario')->whereDate('fecha', '>=', $desde)->get()->keyBy(fn ($c) => $c->fecha->toDateString());
        $movsPorDia = MovimientoInventario::where('created_at', '>=', $desde)->get(['created_at'])
            ->groupBy(fn ($m) => $m->created_at->toDateString())->map->count();
        $historial = collect(range(0, 13))->map(fn ($i) => today()->subDays($i))->map(fn ($d) => [
            'dia' => $d, 'cierre' => $cierres[$d->toDateString()] ?? null, 'movimientos' => $movsPorDia[$d->toDateString()] ?? 0,
        ]);
        // Indicador TCI: tiempo promedio de cierre de los días cerrados con tiempo medido
        $conTiempo = $cierres->whereNotNull('segundos');
        $tci = [
            'promedio' => $conTiempo->count() ? (int) round($conTiempo->avg('segundos')) : null,
            'dias'     => $conTiempo->count(),
            'cerrados' => $cierres->count(),
            'con_movimiento' => $historial->where('movimientos', '>', 0)->count(),
        ];
        $abierto = now()->timestamp;   // para medir cuánto tarda el cierre (TCI)

        return view('reportes.cierre', $datos + compact('historial', 'tci', 'abierto'));
    }

    public function guardar(Request $request)
    {
        $dia = $this->dia($request);
        $request->validate(['observacion' => 'nullable|string|max:300', 'abierto' => 'nullable|integer']);
        $filas = CierreDiario::calcular($dia);
        $t = CierreDiario::totales($filas);
        $filasProd = CierreDiario::calcularProductos($dia);
        $tp = CierreDiario::totales($filasProd);

        // Tiempo de cierre (TCI): desde que se abrió la pantalla hasta que se confirmó (máx. 2 horas)
        $segundos = $request->filled('abierto') ? now()->timestamp - (int) $request->abierto : null;
        if ($segundos !== null && ($segundos < 1 || $segundos > 7200)) $segundos = null;

        $solo = fn ($f) => collect($f)->only(['id', 'nombre', 'unidad', 'inicial', 'entradas', 'salidas', 'ajuste', 'final', 'costo', 'valor_final', 'movimientos'])->all();
        $existia = CierreInventario::whereDate('fecha', $dia)->first();
        $datos = [
            'id_usuario'        => auth()->id(),
            'insumos'           => $t['insumos'],
            'con_movimiento'    => $t['con_movimiento'],
            'valor_entradas'    => $t['valor_entradas'],
            'valor_salidas'     => $t['valor_salidas'],
            'valor_final'       => $t['valor_final'],
            'valor_productos'   => $tp['valor_final'],
            'bajo_minimo'       => $t['bajo_minimo'],
            'segundos'          => $segundos,
            'observacion'       => $request->observacion,
            'detalle'           => $filas->map($solo)->all(),
            'detalle_productos' => $filasProd->map($solo)->all(),
        ];
        $existia ? $existia->update($datos) : CierreInventario::create($datos + ['fecha' => $dia->toDateString()]);

        return redirect()->route('reportes.cierre', ['fecha' => $dia->toDateString()])->with('success',
            ($existia ? 'Cierre actualizado' : 'Cierre registrado') . ' para el ' . $dia->locale('es')->translatedFormat('l d \d\e F')
            . ": {$t['insumos']} insumos y {$tp['insumos']} productos, valor del almacén S/ " . number_format($t['valor_final'], 2) . '.'
            . ($segundos ? ' Tiempo de cierre: ' . CierreInventario::duracion($segundos) . '.' : '')
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
        $esProd = $d['de'] === 'productos';
        $filas = $d['vista']->map(fn ($f) => [
            $f['nombre'], $f['unidad'], $f['inicial'], $f['entradas'], $f['salidas'], $f['ajuste'], $f['final'],
            round($f['costo'], 2), $f['valor_final'], $f['movimientos'], $f['estado'],
        ])->values()->all();
        $filas[] = [];
        $filas[] = ['TOTAL (' . count($d['vista']) . ')', '', '', '', '', '', '', '', round($d['vista']->sum('valor_final'), 2), $d['vista']->sum('movimientos'), ''];
        $titulo = 'Cierre de inventario del ' . $d['dia']->format('d/m/Y') . ($esProd ? ' — productos terminados' : ' — insumos')
            . ($d['cierre'] ? ' — cerrado por ' . ($d['cierre']->usuario->nombre ?? '') . ' a las ' . $d['cierre']->updated_at->format('H:i')
                . ($d['cierre']->duracion ? ' (tardó ' . $d['cierre']->duracion . ')' : '') : ' — sin registrar')
            . ($d['filtroTabla'] ? ' — ' . $d['filtroTabla'] : '');

        return Excel::download(new TablaExport('Cierre ' . $d['dia']->format('d-m-Y'),
            [$esProd ? 'Producto' : 'Insumo', 'Unidad', 'Saldo inicial', 'Entradas', 'Salidas', 'Ajustes', 'Saldo final', 'Costo unit. (S/)', 'Valor final (S/)', 'Movimientos', 'Estado'],
            $filas, $titulo), ($esProd ? 'cierre-productos-' : 'cierre-inventario-') . $d['dia']->format('Y-m-d') . '.xlsx');
    }
}
