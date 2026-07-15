<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('tiempos_baseline', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_operacion')->unique();
            $table->decimal('segundos_manual', 8, 2)->comment('Tiempo muerto inicial (pre-test), obtenido por observación manual');
            $table->timestamp('updated_at')->nullable();
        });

        // Valores iniciales en 0; el investigador los actualiza con su ficha de observación (pre-test)
        DB::table('tiempos_baseline')->insert([
            ['tipo_operacion' => 'busqueda_producto',  'segundos_manual' => 0, 'updated_at' => now()],
            ['tipo_operacion' => 'verificacion_stock', 'segundos_manual' => 0, 'updated_at' => now()],
            ['tipo_operacion' => 'registro_venta',     'segundos_manual' => 0, 'updated_at' => now()],
        ]);
    }
    public function down(): void {
        Schema::dropIfExists('tiempos_baseline');
    }
};
