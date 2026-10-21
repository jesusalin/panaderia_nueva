<?php

namespace App\Console\Commands;

use App\Models\MateriaPrima;
use App\Models\OrdenAutomatica;
use Illuminate\Console\Command;

/**
 * Revisa el inventario y deja creada una orden de reposición pendiente por
 * cada insumo con stock bajo que todavía no tenga una. Es la versión
 * automática del botón "Generar órdenes" (OrdenesAutomaticasController@generar):
 * se programa a diario en routes/console.php para que el almacenero llegue en
 * la mañana y las órdenes ya estén listas para convertir en compra.
 *
 * Uso manual:  php artisan inventario:revisar-stock
 */
class RevisarStockBajo extends Command
{
    protected $signature = 'inventario:revisar-stock';

    protected $description = 'Genera órdenes automáticas de reposición para los insumos con stock bajo';

    public function handle(): int
    {
        $creadas = 0;

        $materias = MateriaPrima::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->get();

        foreach ($materias as $materia) {
            $yaExiste = OrdenAutomatica::where('id_materia', $materia->id)
                ->where('estado', 'pendiente')->exists();

            if ($yaExiste) {
                continue;
            }

            OrdenAutomatica::create([
                'id_materia'        => $materia->id,
                'id_proveedor'      => $materia->id_proveedor,
                'stock_al_generar'  => $materia->stock_actual,
                'stock_minimo'      => $materia->stock_minimo,
                'cantidad_sugerida' => $materia->cantidad_reposicion ?? ($materia->stock_minimo * 2),
                'estado'            => 'pendiente',
            ]);
            $creadas++;
        }

        $this->info($creadas > 0
            ? "Se generaron {$creadas} orden(es) automática(s) de reposición."
            : 'No hay insumos con stock bajo sin orden pendiente.');

        return self::SUCCESS;
    }
}
