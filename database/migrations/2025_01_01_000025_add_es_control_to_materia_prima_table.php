<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Insumo de control": los insumos que se cuentan en el conteo rápido del PER
 * (indicador de la tesis). El admin los marca en el formulario del insumo.
 * Si la base ya tiene insumos, se marcan los 10 de la ficha de la tesis por su nombre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materia_prima', function (Blueprint $table) {
            if (!Schema::hasColumn('materia_prima', 'es_control')) {
                $table->boolean('es_control')->default(false)->after('estado');
            }
        });

        if (DB::table('materia_prima')->where('es_control', true)->exists()) return;
        foreach (['harina', 'azúcar', 'sal', 'levadura', 'manteca', 'mantequilla', 'huevo', 'leche', 'aceite', 'margarina'] as $palabra) {
            $id = DB::table('materia_prima')->where('es_control', false)->where('estado', 'activo')
                ->where('nombre', 'like', $palabra . '%')->orderBy('id')->value('id');
            if ($id) DB::table('materia_prima')->where('id', $id)->update(['es_control' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('materia_prima', function (Blueprint $table) {
            $table->dropColumn('es_control');
        });
    }
};
