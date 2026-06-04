<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kardex_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_producto')->constrained('productos');
            $table->foreignId('id_usuario')->constrained('usuarios');
            $table->enum('tipo', ['entrada', 'salida']);
            $table->enum('motivo', ['produccion', 'venta', 'ajuste_manual', 'devolucion']);
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->integer('cantidad');
            $table->integer('stock_antes');
            $table->integer('stock_despues');
            $table->text('observacion')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('kardex_productos');
    }
};
