<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra cada conteo físico de inventario: cuánto decía el sistema
     * (stock_sistema) vs. cuánto había realmente contando a mano
     * (stock_fisico). Con esto se calcula la Exactitud del Inventario
     * (OE2 de la tesis) con la fórmula real, no con un proxy.
     */
    public function up(): void
    {
        Schema::create('conteos_fisicos', function (Blueprint $table) {
            $table->id();
            // Agrupa todos los ítems contados en la misma sesión/jornada de conteo
            $table->string('lote', 20);
            $table->foreignId('id_materia')->constrained('materia_prima');
            $table->foreignId('id_usuario')->constrained('usuarios');
            $table->decimal('stock_sistema', 10, 3);
            $table->decimal('stock_fisico', 10, 3);
            $table->decimal('diferencia', 10, 3);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index('lote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conteos_fisicos');
    }
};
