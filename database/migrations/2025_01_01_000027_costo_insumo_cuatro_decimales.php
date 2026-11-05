<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El costo de los insumos ahora es el costo promedio ponderado: se guarda con 4 decimales
 * para que al mezclar y al anular compras no se acumulen errores de redondeo
 * (en pantalla se sigue mostrando con 2).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('materia_prima', function (Blueprint $table) {
            $table->decimal('costo_unitario', 12, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('materia_prima', function (Blueprint $table) {
            $table->decimal('costo_unitario', 10, 2)->default(0)->change();
        });
    }
};
