<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Etiqueta libre para que el admin identifique qué es cada usuario
            // (ej: "Almacén", "Caja 1", "Ventas mostrador"). Es solo descriptivo,
            // no afecta los permisos reales del usuario.
            $table->string('apodo', 50)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('apodo');
        });
    }
};
