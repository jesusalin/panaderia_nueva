<?php

namespace App\Console\Commands;

use App\Models\MateriaPrima;
use App\Models\OrdenAutomatica;
use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Revisa el inventario y deja creada una orden de reposición pendiente por
 * cada insumo con stock bajo que todavía no tenga una. Es la versión
 * automática del botón "Generar órdenes" (OrdenesAutomaticasController@generar):
 * se programa a diario en routes/console.php para que el almacenero llegue en
 * la mañana y las órdenes ya estén listas para convertir en compra.
 *
 * Si hay un correo configurado (ALERTA_STOCK_CORREO en el .env) y existe algo
 * con stock bajo, además envía un resumen por correo. Sin correo configurado,
 * no envía nada y el resto funciona igual.
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

        $this->enviarAlertaPorCorreo($materias, $creadas);

        return self::SUCCESS;
    }

    /**
     * Resumen diario de stock bajo por correo. Un fallo al enviar (SMTP caído,
     * credenciales mal puestas) se registra en el log pero nunca rompe el comando,
     * para que las órdenes ya generadas no se pierdan por un problema de correo.
     */
    private function enviarAlertaPorCorreo($insumos, int $ordenesCreadas): void
    {
        $destinatarios = array_filter(array_map('trim', explode(',', (string) config('inventario.alerta_correo'))));
        if (empty($destinatarios)) {
            return; // sin correo configurado: no se envía nada
        }

        $productos = Producto::where('estado', 'activo')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->orderBy('nombre')->get();

        if ($insumos->isEmpty() && $productos->isEmpty()) {
            return; // nada que avisar
        }

        try {
            $html = view('emails.stock-bajo', [
                'insumos' => $insumos->load('unidad'),
                'productos' => $productos,
                'ordenesCreadas' => $ordenesCreadas,
            ])->render();

            Mail::html($html, function ($mensaje) use ($destinatarios, $insumos, $productos) {
                $mensaje->to($destinatarios)
                    ->subject('Muruwasi · Stock bajo (' . ($insumos->count() + $productos->count()) . ' ítems)');
            });

            $this->info('Resumen enviado por correo a: ' . implode(', ', $destinatarios));
        } catch (\Throwable $e) {
            Log::error('inventario:revisar-stock — no se pudo enviar el correo: ' . $e->getMessage());
            $this->warn('No se pudo enviar el correo (revisa storage/logs/laravel.log).');
        }
    }
}
