<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Se actualiza en cada petición autenticada (ver middleware
            // ActualizarUltimoAcceso) y sirve para mostrar quién está
            // conectado ahora mismo en el listado de Usuarios.
            $table->timestamp('ultimo_acceso')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('ultimo_acceso');
        });
    }
};
