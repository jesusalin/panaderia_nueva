<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Historial de actividad: quién hizo qué y cuándo (inicios de sesión,
        // registros, ediciones, eliminaciones…). Lo consulta el admin en
        // Administración → Historial de actividad.
        Schema::create('actividad_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('usuario_texto', 100)->nullable();   // nombre/usuario al momento (o el que se intentó usar)
            $table->string('accion', 30);                       // inicio_sesion, cierre_sesion, login_fallido, crear, editar, eliminar, otra
            $table->string('modulo', 40)->nullable();
            $table->string('descripcion', 255);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['id_usuario', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_usuarios');
    }
};
