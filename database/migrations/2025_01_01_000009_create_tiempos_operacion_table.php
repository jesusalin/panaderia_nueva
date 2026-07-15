<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tiempos_operacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuarios');
            $table->enum('tipo_operacion', ['busqueda_producto', 'verificacion_stock', 'registro_venta']);
            $table->unsignedInteger('duracion_ms');
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('tiempos_operacion');
    }
};
