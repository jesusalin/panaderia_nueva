<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes: persona de contacto (en bodegas, colegios y restaurantes no siempre es el dueño)
 * y notas para el pedido (ej. "pide 200 panes de lunes a viernes, paga a fin de mes").
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'contacto')) $table->string('contacto', 100)->nullable()->after('tipo');
            if (!Schema::hasColumn('clientes', 'notas')) $table->string('notas', 300)->nullable()->after('referencia');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['contacto', 'notas']);
        });
    }
};
