<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Orden personalizado del menú lateral (lo arma cada usuario con
            // "Ordenar menú", arrastrando y soltando). Formato:
            // {"secciones": ["sec-inventario", ...], "items": {"sec-inventario": ["materia-prima", ...]}}
            // NULL = orden original del sistema.
            $table->json('orden_menu')->nullable()->after('ultimo_acceso');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('orden_menu');
        });
    }
};
