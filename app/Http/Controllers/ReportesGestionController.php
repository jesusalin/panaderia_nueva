<?php

namespace App\Http\Controllers;

use App\Exports\TablaExport;
use App\Models\Compra;
use App\Models\KardexProducto;
use App\Models\MovimientoInventario;
use App\Models\Proveedor;
use App\Support\Cantidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

/** Reportes de gestión: Mermas (pérdidas) y Compras (gasto y precios). */
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
}
