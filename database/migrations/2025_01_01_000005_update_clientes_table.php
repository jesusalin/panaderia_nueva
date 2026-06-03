<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clientes', function (Blueprint $table) {
            $table->enum('tipo', ['bodega', 'supermercado', 'colegio', 'restaurante', 'panaderia', 'particular', 'otro'])->default('particular')->after('nombre');
            $table->string('ruc', 20)->nullable()->after('tipo');
            $table->string('direccion', 255)->nullable()->after('email');
            $table->string('distrito', 100)->nullable()->after('direccion');
            $table->string('referencia', 255)->nullable()->after('distrito');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo')->after('referencia');
        });
    }

    public function down(): void {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'ruc', 'direccion', 'distrito', 'referencia', 'estado']);
        });
    }
};
