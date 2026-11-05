<?php

namespace App\Support;

use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cierre diario de inventario: para cada insumo activo, el saldo con el que empezó
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
    /** @return Collection de filas (arrays) ordenadas por nombre */
    public static function calcular(Carbon $dia): Collection
    {
        $inicio = $dia->copy()->startOfDay();
        $fin    = $dia->copy()->endOfDay();

        $materias = MateriaPrima::with('unidad')->where('estado', 'activo')->orderBy('nombre')->get();
        $ids = $materias->pluck('id');

        // Último movimiento antes del día, por insumo
        $previos = MovimientoInventario::whereIn('id', MovimientoInventario::selectRaw('MAX(id)')
                ->whereIn('id_materia', $ids)->where('created_at', '<', $inicio)->groupBy('id_materia'))
            ->get()->keyBy('id_materia');
        // Primer movimiento desde el inicio del día (para insumos sin historia anterior)
        $siguientes = MovimientoInventario::whereIn('id', MovimientoInventario::selectRaw('MIN(id)')
                ->whereIn('id_materia', $ids)->where('created_at', '>=', $inicio)->groupBy('id_materia'))
            ->get()->keyBy('id_materia');
        // Movimientos del día
        $delDia = MovimientoInventario::whereIn('id_materia', $ids)->whereBetween('created_at', [$inicio, $fin])
            ->orderBy('created_at')->orderBy('id')->get()->groupBy('id_materia');

        return $materias->map(function ($m) use ($previos, $siguientes, $delDia) {
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
            $costo = (float) $m->costo_unitario;

            return [
                'id'          => $m->id,
                'nombre'      => $m->nombre,
                'unidad'      => $m->unidad->abreviatura ?? '',
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
                'bajo'        => $final <= (float) $m->stock_minimo,
                // saldo inicial + entradas − salidas ± ajustes = saldo final
                'cuadra'      => abs($inicial + $entradas - $salidas + $ajuste - $final) < 0.001,
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
