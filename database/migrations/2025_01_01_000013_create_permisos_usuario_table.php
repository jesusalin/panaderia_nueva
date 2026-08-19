<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuarios')->onDelete('cascade');
            // Clave del módulo: catalogo, inventario, produccion, compras, clientes, ventas, reportes
            $table->string('modulo', 30);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->unique(['id_usuario', 'modulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos_usuario');
    }
};
