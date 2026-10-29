<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ícono y color que el admin elige para cada categoría
 * (si quedan vacíos, el sistema adivina el ícono por el nombre y usa el color de la marca).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('categorias', 'icono')) $table->string('icono', 40)->nullable()->after('descripcion');
            if (!Schema::hasColumn('categorias', 'color')) $table->string('color', 7)->nullable()->after('icono');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn(['icono', 'color']);
        });
    }
};
