<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('materia_prima', function (Blueprint $table) {
            $table->foreignId('id_proveedor')->nullable()
                ->after('id_unidad')
                ->constrained('proveedores')->onDelete('set null');
            $table->decimal('cantidad_reposicion', 10, 3)->nullable()
                ->after('stock_minimo')
                ->comment('Cantidad sugerida a comprar cuando se genera orden automática');
        });
    }
    public function down(): void {
        Schema::table('materia_prima', function (Blueprint $table) {
            $table->dropForeign(['id_proveedor']);
            $table->dropColumn(['id_proveedor', 'cantidad_reposicion']);
        });
    }
};
