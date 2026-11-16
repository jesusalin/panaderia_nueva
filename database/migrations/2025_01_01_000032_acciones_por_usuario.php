<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acciones que el admin le puede quitar a un usuario dentro de una pantalla (ver Usuario::ACCIONES).
 * Se guardan en permisos_usuario como "sin:catalogo.productos.editar", que no entra en 30 caracteres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permisos_usuario', function (Blueprint $table) {
            $table->string('modulo', 60)->change();
        });
    }

    public function down(): void
    {
        Schema::table('permisos_usuario', function (Blueprint $table) {
            $table->string('modulo', 30)->change();
        });
    }
};
