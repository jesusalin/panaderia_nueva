<?php

namespace App\Support;

use App\Models\KardexProducto;
use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cierre diario de inventario: para cada insumo (y cada producto terminado) activo, el saldo con el que empezó
 * el día, lo que entró, lo que salió, los ajustes y el saldo con el que terminó.
 *
 * Todo sale del kardex (movimientos_inventario):
 *   - saldo inicial = stock_después del último movimiento ANTES del día
 *                     (si no hay, stock_antes del primero del día o posterior; si tampoco, el stock actual)
 *   - entradas / salidas = variación real de cada movimiento del día (KardexCalculo::normalizar),
 *                     separando los ajustes (conteos y correcciones) en su propia columna
 *   - saldo final  = stock_después del último movimiento del día (o el inicial si no hubo)
 */
class CierreDiario
{
    /** Insumos activos del día. @return Collection de filas (arrays) ordenadas por nombre */
    public static function calcular(Carbon $dia): Collection
    {
        $materias = MateriaPrima::with('unidad')->where('estado', 'activo')->orderBy('nombre')->get();
        return self::filas($dia, $materias, MovimientoInventario::class, 'id_materia',
            fn ($m) => $m->unidad->abreviatura ?? '', fn ($m) => (float) $m->costo_unitario);
    }

    /** Productos terminados activos del día (entradas = producción, salidas = ventas y mermas). */
    public static function calcularProductos(Carbon $dia): Collection
    {
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();
        return self::filas($dia, $productos, KardexProducto::class, 'id_producto',
            fn ($p) => 'uds', fn ($p) => (float) ($p->costo_produccion ?? 0));
    }

    private static function filas(Carbon $dia, Collection $items, string $modelo, string $fk, callable $unidad, callable $costoDe): Collection
    {
        $inicio = $dia->copy()->startOfDay();
        $fin    = $dia->copy()->endOfDay();
        $ids = $items->pluck('id');

        // Último movimiento antes del día, por ítem
        $previos = $modelo::whereIn('id', $modelo::selectRaw('MAX(id)')
                ->whereIn($fk, $ids)->where('created_at', '<', $inicio)->groupBy($fk))
            ->get()->keyBy($fk);
        // Primer movimiento desde el inicio del día (para ítems sin historia anterior)
        $siguientes = $modelo::whereIn('id', $modelo::selectRaw('MIN(id)')
                ->whereIn($fk, $ids)->where('created_at', '>=', $inicio)->groupBy($fk))
            ->get()->keyBy($fk);
        // Movimientos del día
        $delDia = $modelo::whereIn($fk, $ids)->whereBetween('created_at', [$inicio, $fin])
            ->orderBy('created_at')->orderBy('id')->get()->groupBy($fk);

        return $items->map(function ($m) use ($previos, $siguientes, $delDia, $unidad, $costoDe) {
            $inicial = isset($previos[$m->id]) ? (float) $previos[$m->id]->stock_despues
                : (isset($siguientes[$m->id]) && $siguientes[$m->id]->stock_antes !== null ? (float) $siguientes[$m->id]->stock_antes : (float) $m->stock_actual);

            $entradas = $salidas = $ajusteMas = $ajusteMenos = 0.0;
            $final = $inicial;
            $movs = $delDia[$m->id] ?? collect();
            $porMotivo = [];
            foreach ($movs as $mv) {
                $n = KardexCalculo::normalizar($mv->stock_antes, $mv->stock_despues, $mv->tipo, $mv->cantidad, $mv->motivo);
                if ($n['es_ajuste']) { $ajusteMas += $n['entrada']; $ajusteMenos += $n['salida']; }
                else { $entradas += $n['entrada']; $salidas += $n['salida']; }
                $porMotivo[$mv->motivo] = ($porMotivo[$mv->motivo] ?? 0) + 1;
                $final = (float) $mv->stock_despues;
            }
            $ajuste = $ajusteMas - $ajusteMenos;
            $costo = $costoDe($m);
            $cuadra = abs($inicial + $entradas - $salidas + $ajuste - $final) < 0.001;
            $bajo = $final <= (float) $m->stock_minimo;

            return [
                'id'          => $m->id,
                'nombre'      => $m->nombre,
                'unidad'      => $unidad($m),
                'inicial'     => round($inicial, 3),
                'entradas'    => round($entradas, 3),
                'salidas'     => round($salidas, 3),
                'ajuste'      => round($ajuste, 3),
                'final'       => round($final, 3),
                'minimo'      => (float) $m->stock_minimo,
                'costo'       => round($costo, 4),
                'valor_final' => round($final * $costo, 2),
                'valor_entradas' => round($entradas * $costo, 2),
                'valor_salidas'  => round($salidas * $costo, 2),
                'movimientos' => $movs->count(),
                'motivos'     => $porMotivo,
                'bajo'        => $bajo,
                // saldo inicial + entradas − salidas ± ajustes = saldo final
                'cuadra'      => $cuadra,
                'estado'      => !$cuadra ? 'No cuadra' : ($bajo ? 'Bajo el mínimo' : 'Normal'),
            ];
        })->values();
    }

    /** Totales de un cierre */
    public static function totales(Collection $filas): array
    {
        return [
            'insumos'        => $filas->count(),
            'con_movimiento' => $filas->where('movimientos', '>', 0)->count(),
            'movimientos'    => $filas->sum('movimientos'),
            'valor_entradas' => round($filas->sum('valor_entradas'), 2),
            'valor_salidas'  => round($filas->sum('valor_salidas'), 2),
            'valor_final'    => round($filas->sum('valor_final'), 2),
            'bajo_minimo'    => $filas->where('bajo', true)->count(),
            'no_cuadran'     => $filas->where('cuadra', false)->count(),
        ];
    }
}
