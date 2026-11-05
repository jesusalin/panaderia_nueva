<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Tabla de cierres diarios de inventario (quién cerró el día, totales y la foto de cada insumo).
 * - Nuevas pantallas de Reportes: Cierre Diario, Mermas y Compras. Quien ya tenía algún
 *   reporte (stock, kardex o resumen) las recibe para no perder nada.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cierres_inventario')) {
            Schema::create('cierres_inventario', function (Blueprint $table) {
                $table->id();
                $table->date('fecha')->unique();
                $table->foreignId('id_usuario')->constrained('usuarios');
                $table->unsignedInteger('insumos');
                $table->unsignedInteger('con_movimiento');
                $table->decimal('valor_entradas', 12, 2)->default(0);
                $table->decimal('valor_salidas', 12, 2)->default(0);
                $table->decimal('valor_final', 12, 2)->default(0);
                $table->unsignedInteger('bajo_minimo')->default(0);
                $table->string('observacion', 300)->nullable();
                $table->json('detalle');
                $table->timestamps();
            });
        }

        $conReportes = DB::table('permisos_usuario')
            ->whereIn('modulo', ['reportes', 'reportes.resumen', 'reportes.stock', 'reportes.kardex'])
            ->distinct()->pluck('id_usuario');
        foreach ($conReportes as $id) {
            foreach (['reportes.cierre', 'reportes.mermas', 'reportes.compras'] as $clave) {
                DB::table('permisos_usuario')->insertOrIgnore(['id_usuario' => $id, 'modulo' => $clave, 'created_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permisos_usuario')->whereIn('modulo', ['reportes.cierre', 'reportes.mermas', 'reportes.compras'])->delete();
        Schema::dropIfExists('cierres_inventario');
    }
};
