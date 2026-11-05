<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Compras más completas:
 *  - quién y cuándo recibió el pedido, y cuánto llegó de verdad de cada insumo
 *  - quién, cuándo y por qué se anuló
 *  - si los precios se escribieron con IGV incluido
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            if (!Schema::hasColumn('compras', 'incluye_igv')) {
                $table->boolean('incluye_igv')->default(false)->after('total');
            }
            if (!Schema::hasColumn('compras', 'recibida_at')) {
                $table->dateTime('recibida_at')->nullable()->after('estado');
                $table->foreignId('id_usuario_recibe')->nullable()->after('recibida_at')->constrained('usuarios')->nullOnDelete();
                $table->string('nota_recepcion', 200)->nullable()->after('id_usuario_recibe');
            }
            if (!Schema::hasColumn('compras', 'anulada_at')) {
                $table->dateTime('anulada_at')->nullable()->after('nota_recepcion');
                $table->foreignId('id_usuario_anula')->nullable()->after('anulada_at')->constrained('usuarios')->nullOnDelete();
                $table->string('motivo_anulacion', 200)->nullable()->after('id_usuario_anula');
            }
        });

        Schema::table('compra_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('compra_detalles', 'cantidad_recibida')) {
                $table->decimal('cantidad_recibida', 10, 3)->nullable()->after('cantidad');
            }
        });

        // Lo que ya estaba recibido: llegó completo y la fecha es la última actualización
        DB::table('compras')->where('estado', 'recibida')->whereNull('recibida_at')
            ->update(['recibida_at' => DB::raw('updated_at')]);
        DB::table('compras')->where('estado', 'anulada')->whereNull('anulada_at')
            ->update(['anulada_at' => DB::raw('updated_at')]);
        $recibidas = DB::table('compras')->where('estado', 'recibida')->pluck('id');
        if ($recibidas->isNotEmpty()) {
            DB::table('compra_detalles')->whereIn('id_compra', $recibidas)->whereNull('cantidad_recibida')
                ->update(['cantidad_recibida' => DB::raw('cantidad')]);
        }
    }

    public function down(): void
    {
        Schema::table('compra_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('compra_detalles', 'cantidad_recibida')) $table->dropColumn('cantidad_recibida');
        });
        Schema::table('compras', function (Blueprint $table) {
            if (Schema::hasColumn('compras', 'id_usuario_anula')) $table->dropConstrainedForeignId('id_usuario_anula');
            if (Schema::hasColumn('compras', 'id_usuario_recibe')) $table->dropConstrainedForeignId('id_usuario_recibe');
            foreach (['incluye_igv', 'recibida_at', 'nota_recepcion', 'anulada_at', 'motivo_anulacion'] as $c) {
                if (Schema::hasColumn('compras', $c)) $table->dropColumn($c);
            }
        });
    }
};
