<?php

namespace App\Http\Controllers;

use App\Exports\TablaExport;
use App\Models\Compra;
use App\Models\KardexProducto;
use App\Models\MovimientoInventario;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Models\Venta;
use App\Support\Cantidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

/** Reportes de gestión: Mermas (pérdidas), Compras (gasto y precios) y Ventas (ingresos y margen). */
class ReportesGestionController extends Controller
{
    public const PERIODOS = ['mes' => 'Este mes', 'anterior' => 'Mes anterior', '30' => 'Últimos 30 días', '90' => 'Últimos 3 meses', 'anio' => 'Este año', 'todo' => 'Todo'];

    /** Motivos de merma, para agrupar (se reconocen por el texto que se escribió) */
    public const MOTIVOS = ['vencido' => 'Se venció', 'roto' => 'Se rompió o derramó', 'malestado' => 'En mal estado', 'otro' => 'Otro motivo'];

    /** @return array{0: ?Carbon, 1: Carbon, 2: string, 3: string} desde, hasta, clave, texto */
    private function periodo(Request $request, string $porDefecto = 'mes'): array
    {
        $request->validate(['desde' => 'nullable|date', 'hasta' => 'nullable|date', 'periodo' => 'nullable|string']);
        if ($request->filled('desde') || $request->filled('hasta')) {
            $desde = $request->filled('desde') ? Carbon::parse($request->desde)->startOfDay() : null;
            $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta)->endOfDay() : now()->endOfDay();
            return [$desde, $hasta, '', ($desde ? $desde->format('d/m/Y') : 'Inicio') . ' al ' . $hasta->format('d/m/Y')];
        }
        $p = $request->input('periodo', $porDefecto);
        if (!isset(self::PERIODOS[$p])) $p = $porDefecto;
        [$desde, $hasta] = match ($p) {
            'mes'      => [today()->startOfMonth(), now()->endOfDay()],
            'anterior' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()->endOfDay()],
            '30'       => [today()->subDays(29), now()->endOfDay()],
            '90'       => [today()->subDays(89), now()->endOfDay()],
            'anio'     => [today()->startOfYear(), now()->endOfDay()],
            default    => [null, now()->endOfDay()],
        };
        $texto = match ($p) {
            'mes'      => ucfirst(today()->locale('es')->translatedFormat('F Y')),
            'anterior' => ucfirst(today()->subMonthNoOverflow()->locale('es')->translatedFormat('F Y')),
            'todo'     => 'Todo el historial',
            default    => $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y'),
        };
        return [$desde, $hasta, $p, $texto];
    }

    public static function motivoMerma(?string $texto): string
    {
        $t = mb_strtolower((string) $texto);
        return match (true) {
            str_contains($t, 'venc')                                                   => 'vencido',
            (bool) preg_match('/rot[oa]s?\b|romp|derram|cay[oó]|quebr/u', $t)          => 'roto',
            (bool) preg_match('/mal estado|malog|humedad|plaga|podrid|hongo|fr[ií]o|quem/u', $t) => 'malestado',
            default                                                                    => 'otro',
        };
    }

    // ═══════════════════════════════ MERMAS ═══════════════════════════════

    private function datosMermas(Request $request): array
    {
        [$desde, $hasta, $periodo, $textoPeriodo] = $this->periodo($request);
        $tipo = in_array($request->tipo, ['insumos', 'productos'], true) ? $request->tipo : 'todos';
        $rango = fn ($q) => $q->when($desde, fn ($x) => $x->where('created_at', '>=', $desde))->where('created_at', '<=', $hasta);

        $filas = collect();
        if ($tipo !== 'productos') {
            $rango(MovimientoInventario::with(['materia.unidad', 'usuario'])->where('motivo', 'merma'))->get()
                ->each(function ($m) use ($filas) {
                    $u = $m->materia->unidad->abreviatura ?? '';
                    $filas->push([
                        'fecha' => $m->created_at, 'tipo' => 'insumo', 'id' => $m->id_materia, 'nombre' => $m->materia->nombre ?? '—',
                        'unidad' => $u, 'cantidad' => (float) $m->cantidad, 'texto' => Cantidad::conUnidad($m->cantidad, $u),
                        'valor' => round($m->cantidad * (float) ($m->materia->costo_unitario ?? 0), 2),
                        'motivo' => self::motivoMerma($m->observacion), 'detalle' => preg_replace('/^Merma:\s*/i', '', (string) $m->observacion),
                        'usuario' => $m->usuario->apodo ?? $m->usuario->nombre ?? '—',
                    ]);
                });
        }
        if ($tipo !== 'insumos') {
            $rango(KardexProducto::with(['producto', 'usuario'])->where('tipo', 'salida')->where('motivo', 'ajuste_manual')->where('observacion', 'like', 'Merma%'))->get()
                ->each(function ($k) use ($filas) {
                    $filas->push([
                        'fecha' => $k->created_at, 'tipo' => 'producto', 'id' => $k->id_producto, 'nombre' => $k->producto->nombre ?? '—',
                        'unidad' => 'und', 'cantidad' => (float) $k->cantidad, 'texto' => (int) $k->cantidad . ' und',
                        'valor' => round($k->cantidad * (float) ($k->producto->costo_produccion ?? 0), 2),
                        'motivo' => self::motivoMerma($k->observacion), 'detalle' => preg_replace('/^Merma:\s*/i', '', (string) $k->observacion),
                        'usuario' => $k->usuario->apodo ?? $k->usuario->nombre ?? '—',
                    ]);
                });
        }
        $filas = $filas->sortByDesc('fecha')->values();

        $porItem = $filas->groupBy(fn ($f) => $f['tipo'] . '-' . $f['id'])->map(fn ($g) => [
            'tipo' => $g->first()['tipo'], 'id' => $g->first()['id'], 'nombre' => $g->first()['nombre'], 'unidad' => $g->first()['unidad'],
            'cantidad' => $g->sum('cantidad'), 'valor' => round($g->sum('valor'), 2), 'veces' => $g->count(),
            'motivo' => $g->countBy('motivo')->sortDesc()->keys()->first(),
        ])->sortByDesc('valor')->values();

        $porMotivo = collect(self::MOTIVOS)->map(fn ($texto, $clave) => [
            'texto' => $texto, 'valor' => round($filas->where('motivo', $clave)->sum('valor'), 2), 'veces' => $filas->where('motivo', $clave)->count(),
        ])->filter(fn ($m) => $m['veces'] > 0)->sortByDesc('valor');

        // Índice de merma de insumos: lo perdido sobre todo lo que salió (producción + merma), en soles
        $usado = (float) MovimientoInventario::query()->join('materia_prima', 'materia_prima.id', '=', 'movimientos_inventario.id_materia')
            ->where('movimientos_inventario.motivo', 'produccion')->where('movimientos_inventario.tipo', 'salida')
            ->when($desde, fn ($x) => $x->where('movimientos_inventario.created_at', '>=', $desde))->where('movimientos_inventario.created_at', '<=', $hasta)
            ->sum(\Illuminate\Support\Facades\DB::raw('movimientos_inventario.cantidad * materia_prima.costo_unitario'));
        $perdidoInsumos = $filas->where('tipo', 'insumo')->sum('valor');
        $indice = ($usado + $perdidoInsumos) > 0 ? round($perdidoInsumos / ($usado + $perdidoInsumos) * 100, 1) : null;

        $total = round($filas->sum('valor'), 2);
        return compact('desde', 'hasta', 'periodo', 'textoPeriodo', 'tipo', 'filas', 'porItem', 'porMotivo', 'total', 'indice');
    }

    public function mermas(Request $request)
    {
        return view('reportes.mermas', $this->datosMermas($request));
    }

    public function mermasPdf(Request $request)
    {
        return Pdf::loadView('reportes.mermas-pdf', $this->datosMermas($request))->setPaper('a4')->stream('reporte-mermas-' . now()->format('Y-m-d') . '.pdf');
    }

    public function mermasExcel(Request $request)
    {
        $d = $this->datosMermas($request);
        $filas = $d['filas']->map(fn ($f) => [
            $f['fecha']->format('d/m/Y H:i'), $f['tipo'] === 'insumo' ? 'Insumo' : 'Producto', $f['nombre'], round($f['cantidad'], 3), $f['unidad'],
            $f['valor'], self::MOTIVOS[$f['motivo']], $f['detalle'], $f['usuario'],
        ])->all();
        $filas[] = [];
        $filas[] = ['TOTAL', '', '', '', '', $d['total'], '', '', ''];
        return Excel::download(new TablaExport('Mermas', ['Fecha', 'Tipo', 'Insumo / producto', 'Cantidad', 'Unidad', 'Valor (S/)', 'Motivo', 'Detalle', 'Registró'],
            $filas, 'Reporte de mermas — ' . $d['textoPeriodo']), 'reporte-mermas-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ═══════════════════════════════ COMPRAS ═══════════════════════════════

    private function datosCompras(Request $request): array
    {
        [$desde, $hasta, $periodo, $textoPeriodo] = $this->periodo($request);
        $idProveedor = $request->integer('proveedor') ?: null;

        $base = Compra::with(['proveedor', 'detalles.materia.unidad'])
            ->when($desde, fn ($q) => $q->whereDate('fecha_compra', '>=', $desde))->whereDate('fecha_compra', '<=', $hasta)
            ->when($idProveedor, fn ($q) => $q->where('id_proveedor', $idProveedor));
        $todas = (clone $base)->orderBy('fecha_compra')->orderBy('id')->get();
        $compras = $todas->where('estado', '!=', 'anulada')->values();

        $tot = [
            'total'      => round($compras->sum('total'), 2),
            'subtotal'   => round($compras->sum('subtotal'), 2),
            'igv'        => round($compras->sum('igv'), 2),
            'n'          => $compras->count(),
            'pendiente'  => round($compras->where('estado', 'pendiente')->sum('total'), 2),
            'pendientes' => $compras->where('estado', 'pendiente')->count(),
            'anuladas'   => $todas->where('estado', 'anulada')->count(),
        ];
        $tot['promedio'] = $tot['n'] ? round($tot['total'] / $tot['n'], 2) : 0;

        $porProveedor = $compras->groupBy('id_proveedor')->map(fn ($g) => [
            'id' => $g->first()->id_proveedor, 'nombre' => $g->first()->proveedor->nombre ?? '—',
            'n' => $g->count(), 'total' => round($g->sum('total'), 2),
        ])->sortByDesc('total')->values();

        // Por insumo: cantidad, gasto sin IGV, precio promedio ponderado y cómo cambió el precio
        $lineas = $compras->flatMap(fn ($c) => $c->detalles->map(fn ($d) => [
            'id' => $d->id_materia, 'materia' => $d->materia, 'fecha' => $c->fecha_compra, 'proveedor' => $c->proveedor->nombre ?? '—',
            'cantidad' => $d->cantidad_real, 'precio' => $c->incluye_igv ? $d->precio_unitario / (1 + Compra::IGV) : (float) $d->precio_unitario,
        ]));
        $porInsumo = $lineas->groupBy('id')->map(function ($g) {
            $g = $g->sortBy('fecha')->values();
            $cant = $g->sum('cantidad');
            $gasto = $g->sum(fn ($l) => $l['cantidad'] * $l['precio']);
            $primero = $g->first()['precio']; $ultimo = $g->last()['precio'];
            return [
                'id' => $g->first()['id'], 'nombre' => $g->first()['materia']->nombre ?? '—', 'unidad' => $g->first()['materia']->unidad->abreviatura ?? '',
                'cantidad' => $cant, 'gasto' => round($gasto, 2), 'veces' => $g->count(),
                'promedio' => $cant > 0 ? round($gasto / $cant, 2) : 0, 'min' => round($g->min('precio'), 2), 'max' => round($g->max('precio'), 2),
                'primero' => round($primero, 2), 'ultimo' => round($ultimo, 2),
                'variacion' => $g->count() > 1 && $primero > 0 ? round(($ultimo - $primero) / $primero * 100) : null,
                'proveedores' => $g->pluck('proveedor')->unique()->values(),
            ];
        })->sortByDesc('gasto')->values();

        // Gasto en el tiempo: por día, semana o mes según el largo del periodo
        $inicio = $desde ?? ($compras->first()?->fecha_compra?->copy() ?? today());
        $dias = $inicio->diffInDays($hasta);
        $agrupar = $dias > 92 ? 'mes' : ($dias > 31 ? 'semana' : 'dia');
        $clave = fn ($f) => match ($agrupar) { 'mes' => $f->format('Y-m'), 'semana' => $f->copy()->startOfWeek()->format('Y-m-d'), default => $f->format('Y-m-d') };
        $porTiempo = $compras->groupBy(fn ($c) => $clave($c->fecha_compra))->map(fn ($g, $k) => [
            'etiqueta' => match ($agrupar) {
                'mes' => ucfirst(Carbon::parse($k . '-01')->locale('es')->translatedFormat('M Y')),
                'semana' => 'Sem. ' . Carbon::parse($k)->format('d/m'),
                default => Carbon::parse($k)->format('d/m'),
            },
            'total' => round($g->sum('total'), 2), 'n' => $g->count(),
        ])->sortKeys()->values();

        $proveedores = Proveedor::orderBy('nombre')->get(['id', 'nombre']);
        return compact('desde', 'hasta', 'periodo', 'textoPeriodo', 'idProveedor', 'proveedores', 'compras', 'tot', 'porProveedor', 'porInsumo', 'porTiempo', 'agrupar');
    }

    public function compras(Request $request)
    {
        return view('reportes.compras', $this->datosCompras($request));
    }

    public function comprasPdf(Request $request)
    {
        return Pdf::loadView('reportes.compras-pdf', $this->datosCompras($request))->setPaper('a4')->stream('reporte-compras-' . now()->format('Y-m-d') . '.pdf');
    }

    public function comprasExcel(Request $request)
    {
        $d = $this->datosCompras($request);
        $filas = $d['porInsumo']->map(fn ($i) => [
            $i['nombre'], round($i['cantidad'], 3), $i['unidad'], $i['veces'], $i['gasto'], $i['promedio'], $i['min'], $i['max'],
            $i['primero'], $i['ultimo'], $i['variacion'] !== null ? $i['variacion'] . '%' : '', $i['proveedores']->join(', '),
        ])->all();
        $filas[] = [];
        $filas[] = ['TOTAL sin IGV', '', '', '', round($d['porInsumo']->sum('gasto'), 2), '', '', '', '', '', '', ''];
        $filas[] = ['TOTAL con IGV', '', '', $d['tot']['n'] . ' compras', $d['tot']['total'], '', '', '', '', '', '', ''];
        return Excel::download(new TablaExport('Compras por insumo',
            ['Insumo', 'Cantidad', 'Unidad', 'Compras', 'Gasto sin IGV (S/)', 'Precio prom.', 'Precio mín.', 'Precio máx.', 'Primer precio', 'Último precio', 'Variación', 'Proveedores'],
            $filas, 'Reporte de compras — ' . $d['textoPeriodo']), 'reporte-compras-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ═══════════════════════════════ VENTAS ═══════════════════════════════

    public const DIAS_SEMANA = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    private function datosVentas(Request $request): array
    {
        [$desde, $hasta, $periodo, $textoPeriodo] = $this->periodo($request);
        $idVendedor = $request->integer('vendedor') ?: null;

        $todas = Venta::with(['cliente', 'usuario', 'detalles.producto.categoria'])
            ->when($desde, fn ($q) => $q->where('fecha_venta', '>=', $desde))->where('fecha_venta', '<=', $hasta)
            ->when($idVendedor, fn ($q) => $q->where('id_usuario', $idVendedor))
            ->orderBy('fecha_venta')->get();
        $ventas = $todas->where('estado', 'completada')->values();
        $anuladas = $todas->where('estado', 'anulada');

        // Por producto: unidades, ingreso, costo (con el costo de producción actual) y ganancia sin IGV
        $porProducto = $ventas->flatMap->detalles->groupBy('id_producto')->map(function ($g) {
            $p = $g->first()->producto;
            $cant = (int) $g->sum('cantidad');
            $ingreso = round($g->sum('subtotal'), 2);
            $costoU = (float) ($p->costo_produccion ?? 0);
            $neto = round($ingreso / (1 + Venta::IGV), 2);
            return [
                'id' => $g->first()->id_producto, 'nombre' => $p->nombre ?? 'Producto eliminado', 'categoria' => $p?->categoria?->nombre ?? '—',
                'cantidad' => $cant, 'ingreso' => $ingreso, 'neto' => $neto,
                'costo' => $costoU > 0 ? round($cant * $costoU, 2) : null,
                'ganancia' => $costoU > 0 ? round($neto - $cant * $costoU, 2) : null,
                'margen' => $costoU > 0 && $neto > 0 ? round(($neto - $cant * $costoU) / $neto * 100) : null,
            ];
        })->sortByDesc('ingreso')->values();

        $total = round($ventas->sum('total'), 2);
        $conCosto = $porProducto->whereNotNull('costo');
        $tot = [
            'total'     => $total,
            'neto'      => round($ventas->sum('subtotal'), 2),
            'igv'       => round($ventas->sum('igv'), 2),
            'n'         => $ventas->count(),
            'unidades'  => (int) $porProducto->sum('cantidad'),
            'ticket'    => $ventas->count() ? round($total / $ventas->count(), 2) : 0,
            'anuladas'  => $anuladas->count(),
            'monto_anulado' => round($anuladas->sum('total'), 2),
            'costo'     => round($conCosto->sum('costo'), 2),
            'ganancia'  => round($conCosto->sum('ganancia'), 2),
            'sin_costo' => $porProducto->whereNull('costo')->count(),
        ];
        $tot['margen'] = $conCosto->sum('neto') > 0 ? round($tot['ganancia'] / $conCosto->sum('neto') * 100) : null;

        // Comparación con el periodo anterior del mismo largo
        $anterior = null;
        if ($desde) {
            $largo = $desde->diffInSeconds($hasta);
            $antHasta = $desde->copy()->subSecond();
            $antDesde = $antHasta->copy()->subSeconds($largo);
            $antTotal = (float) Venta::where('estado', 'completada')->whereBetween('fecha_venta', [$antDesde, $antHasta])
                ->when($idVendedor, fn ($q) => $q->where('id_usuario', $idVendedor))->sum('total');
            $anterior = ['total' => round($antTotal, 2), 'desde' => $antDesde, 'hasta' => $antHasta,
                'variacion' => $antTotal > 0 ? round(($total - $antTotal) / $antTotal * 100) : null];
        }

        // Ingresos en el tiempo: por día, semana o mes según el largo del periodo
        $inicio = $desde ?? ($ventas->first()?->fecha_venta?->copy()->startOfDay() ?? today());
        $dias = $inicio->diffInDays($hasta);
        $agrupar = $dias > 92 ? 'mes' : ($dias > 31 ? 'semana' : 'dia');
        $clave = fn ($f) => match ($agrupar) { 'mes' => $f->format('Y-m'), 'semana' => $f->copy()->startOfWeek()->format('Y-m-d'), default => $f->format('Y-m-d') };
        $porTiempo = $ventas->groupBy(fn ($v) => $clave($v->fecha_venta))->map(fn ($g, $k) => [
            'etiqueta' => match ($agrupar) {
                'mes' => ucfirst(Carbon::parse($k . '-01')->locale('es')->translatedFormat('M Y')),
                'semana' => 'Sem. ' . Carbon::parse($k)->format('d/m'),
                default => Carbon::parse($k)->format('d/m'),
            },
            'total' => round($g->sum('total'), 2), 'n' => $g->count(),
        ])->sortKeys()->values();

        // Horas pico y días de la semana
        $porHora = $ventas->groupBy(fn ($v) => (int) $v->fecha_venta->format('G'))->map(fn ($g, $h) => ['hora' => $h, 'total' => round($g->sum('total'), 2), 'n' => $g->count()])->sortKeys();
        if ($porHora->isNotEmpty()) {
            $porHora = collect(range($porHora->keys()->min(), $porHora->keys()->max()))->map(fn ($h) => $porHora[$h] ?? ['hora' => $h, 'total' => 0, 'n' => 0])->values();
        }
        $horaPico = $porHora->sortByDesc('total')->first();
        $porDiaSemana = collect(self::DIAS_SEMANA)->map(function ($texto, $d) use ($ventas) {
            $g = $ventas->filter(fn ($v) => $v->fecha_venta->dayOfWeekIso === $d);
            return ['texto' => $texto, 'total' => round($g->sum('total'), 2), 'n' => $g->count()];
        });

        $porMedio = $ventas->groupBy('tipo_pago')->map(fn ($g, $k) => Venta::MEDIOS[$k] ?? Venta::MEDIOS['otro'])->map(fn ($m, $k) => $m + [
            'clave' => $k, 'total' => round($ventas->where('tipo_pago', $k)->sum('total'), 2), 'n' => $ventas->where('tipo_pago', $k)->count(),
        ])->sortByDesc('total')->values();
        $porCliente = $ventas->groupBy('id_cliente')->map(fn ($g) => [
            'id' => $g->first()->id_cliente, 'nombre' => $g->first()->cliente->nombre ?? 'Cliente General',
            'n' => $g->count(), 'total' => round($g->sum('total'), 2), 'ultima' => $g->max('fecha_venta'),
        ])->sortByDesc('total')->values();
        $porVendedor = $ventas->groupBy('id_usuario')->map(fn ($g) => [
            'nombre' => $g->first()->usuario->nombre ?? '—',
            'n' => $g->count(), 'total' => round($g->sum('total'), 2), 'anuladas' => $anuladas->where('id_usuario', $g->first()->id_usuario)->count(),
        ])->sortByDesc('total')->values();
        $porCategoria = $porProducto->groupBy('categoria')->map(fn ($g, $c) => ['nombre' => $c, 'ingreso' => round($g->sum('ingreso'), 2), 'cantidad' => $g->sum('cantidad')])
            ->sortByDesc('ingreso')->values();

        $vendedores = Usuario::whereIn('id', Venta::select('id_usuario')->distinct())->orderBy('nombre')->get(['id', 'nombre']);
        return compact('desde', 'hasta', 'periodo', 'textoPeriodo', 'idVendedor', 'vendedores', 'ventas', 'anuladas', 'tot', 'anterior',
            'porProducto', 'porTiempo', 'agrupar', 'porHora', 'horaPico', 'porDiaSemana', 'porMedio', 'porCliente', 'porVendedor', 'porCategoria');
    }

    public function ventas(Request $request)
    {
        return view('reportes.ventas', $this->datosVentas($request));
    }

    public function ventasPdf(Request $request)
    {
        return Pdf::loadView('reportes.ventas-pdf', $this->datosVentas($request))->setPaper('a4')->stream('reporte-ventas-' . now()->format('Y-m-d') . '.pdf');
    }

    public function ventasExcel(Request $request)
    {
        $d = $this->datosVentas($request);
        $filas = $d['porProducto']->map(fn ($p) => [
            $p['nombre'], $p['categoria'], $p['cantidad'], $p['ingreso'], $p['neto'],
            $p['costo'] ?? '', $p['ganancia'] ?? '', $p['margen'] !== null ? $p['margen'] . '%' : 'sin costo',
            $d['tot']['total'] > 0 ? round($p['ingreso'] / $d['tot']['total'] * 100, 1) . '%' : '',
        ])->all();
        $filas[] = [];
        $filas[] = ['TOTAL', '', $d['tot']['unidades'], $d['tot']['total'], $d['tot']['neto'], $d['tot']['costo'], $d['tot']['ganancia'],
            $d['tot']['margen'] !== null ? $d['tot']['margen'] . '%' : '', $d['tot']['n'] . ' ventas'];
        return Excel::download(new TablaExport('Ventas por producto',
            ['Producto', 'Categoría', 'Unidades', 'Ingreso con IGV (S/)', 'Ingreso sin IGV (S/)', 'Costo (S/)', 'Ganancia (S/)', 'Margen', '% de las ventas'],
            $filas, 'Reporte de ventas — ' . $d['textoPeriodo']), 'reporte-ventas-' . now()->format('Y-m-d') . '.xlsx');
    }
}
