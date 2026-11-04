<?php

namespace App\Support;

use App\Models\Producto;
use App\Models\VentaDetalle;
use Illuminate\Support\Collection;

/**
 * Cálculos de producción que se usan en varias pantallas:
 *  - cuánto se puede producir con los insumos que hay,
 *  - cuánto cuestan los insumos de una tanda,
 *  - qué conviene producir hoy.
 */
class PlanProduccion
{
    /**
     * Máximo de unidades que alcanzan los insumos en stock (null si no tiene receta).
     * Devuelve también qué insumo limita.
     *
     * @return array{max:int, limita:?string}|null
     */
    public static function maximo(Producto $p): ?array
    {
        $receta = $p->relationLoaded('receta') ? $p->receta : $p->receta()->with('detalles.materia')->first();
        if (!$receta || $receta->detalles->isEmpty()) return null;

        $rend = max(1, (int) $receta->rendimiento);
        $max = PHP_INT_MAX;
        $limita = null;
        foreach ($receta->detalles as $d) {
            if (!$d->materia || $d->cantidad <= 0) continue;
            $porUnidad = $d->cantidad / $rend;
            $alcanza = (int) floor(($d->materia->stock_actual + 1e-9) / $porUnidad);
            if ($alcanza < $max) { $max = $alcanza; $limita = $d->materia->nombre; }
        }
        return ['max' => $max === PHP_INT_MAX ? 0 : max(0, $max), 'limita' => $limita];
    }

    /** Costo de los insumos para producir $cantidad unidades (null si no tiene receta). */
    public static function costo(Producto $p, int $cantidad): ?float
    {
        $receta = $p->relationLoaded('receta') ? $p->receta : $p->receta()->with('detalles.materia')->first();
        if (!$receta || $receta->detalles->isEmpty()) return null;
        $lotes = $cantidad / max(1, (int) $receta->rendimiento);
        return round($receta->detalles->sum(fn ($d) => $d->cantidad * $lotes * ($d->materia->costo_unitario ?? 0)), 2);
    }

    /**
     * "¿Qué producir hoy?": productos activos con receta que están en su mínimo o cerca
     * (hasta 1.5 veces el mínimo). La cantidad sugerida cubre lo que se vende en un día
     * (promedio de los últimos 7) y deja el stock por encima del mínimo, redondeada a
     * tandas completas de la receta, sin pasar de lo que alcanzan los insumos.
     */
    public static function sugerencias(int $limite = 12): Collection
    {
        $productos = Producto::where('estado', 'activo')
            ->whereHas('receta')
            ->whereRaw('stock_actual <= stock_minimo * 1.5')
            ->with(['categoria', 'receta.detalles.materia.unidad'])
            ->get();
        if ($productos->isEmpty()) return collect();

        $vendido7 = VentaDetalle::whereIn('id_producto', $productos->pluck('id'))
            ->whereHas('venta', fn ($v) => $v->where('estado', 'completada')->where('fecha_venta', '>=', now()->subDays(7)))
            ->selectRaw('id_producto, SUM(cantidad) as total')->groupBy('id_producto')->pluck('total', 'id_producto');

        return $productos->map(function (Producto $p) use ($vendido7) {
            $porDia = round(($vendido7[$p->id] ?? 0) / 7, 1);
            $rend = max(1, (int) $p->receta->rendimiento);
            $objetivo = (int) ceil($porDia + $p->stock_minimo);
            $falta = max($objetivo - $p->stock_actual, $p->stock_minimo * 2 - $p->stock_actual, 1);
            $sugerido = (int) (ceil($falta / $rend) * $rend);
            $max = self::maximo($p);
            $alcanza = $max['max'] ?? 0;
            return (object) [
                'producto'  => $p,
                'porDia'    => $porDia,
                'sugerido'  => $sugerido,
                'alcanza'   => $alcanza,
                'producir'  => min($sugerido, $alcanza),
                'limita'    => $max['limita'] ?? null,
                'urgencia'  => $p->stock_minimo > 0 ? $p->stock_actual / $p->stock_minimo : 0,
            ];
        })->sortBy('urgencia')->take($limite)->values();
    }
}
