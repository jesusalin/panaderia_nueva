<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cierre diario: tiempo que tomó cerrar (indicador TCI de la tesis) y la foto de los
 * productos terminados además de la de los insumos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cierres_inventario', function (Blueprint $table) {
            if (!Schema::hasColumn('cierres_inventario', 'segundos')) {
                $table->unsignedInteger('segundos')->nullable()->after('bajo_minimo');
            }
            if (!Schema::hasColumn('cierres_inventario', 'valor_productos')) {
                $table->decimal('valor_productos', 12, 2)->default(0)->after('valor_final');
            }
            if (!Schema::hasColumn('cierres_inventario', 'detalle_productos')) {
                $table->json('detalle_productos')->nullable()->after('detalle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cierres_inventario', function (Blueprint $table) {
            $table->dropColumn(['segundos', 'valor_productos', 'detalle_productos']);
        });
    }
};
