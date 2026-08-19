<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ordenes_automaticas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_materia')->constrained('materia_prima');
            $table->foreignId('id_proveedor')->nullable()->constrained('proveedores');
            $table->decimal('stock_al_generar', 10, 3);
            $table->decimal('stock_minimo', 10, 3);
            $table->decimal('cantidad_sugerida', 10, 3);
            $table->foreignId('id_compra')->nullable()->constrained('compras')->onDelete('set null');
            $table->enum('estado', ['pendiente', 'convertida', 'descartada'])->default('pendiente');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('ordenes_automaticas');
    }
};
