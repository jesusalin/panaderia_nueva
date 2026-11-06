<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas más completas:
 *  - con cuánto pagó el cliente y su vuelto (efectivo), o el número de operación (Yape, Plin, tarjeta)
 *  - quién, cuándo y por qué se anuló
 *  - nueva pantalla "Reporte de Ventas" para quien ya tenía reportes
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('ventas', 'monto_recibido')) {
                $table->decimal('monto_recibido', 10, 2)->nullable()->after('tipo_pago');
                $table->decimal('vuelto', 10, 2)->nullable()->after('monto_recibido');
                $table->string('numero_operacion', 30)->nullable()->after('vuelto');
            }
            if (!Schema::hasColumn('ventas', 'anulada_at')) {
                $table->dateTime('anulada_at')->nullable()->after('estado');
                $table->foreignId('id_usuario_anula')->nullable()->after('anulada_at')->constrained('usuarios')->nullOnDelete();
                $table->string('motivo_anulacion', 200)->nullable()->after('id_usuario_anula');
            }
        });
        DB::table('ventas')->where('estado', 'anulada')->whereNull('anulada_at')->update(['anulada_at' => DB::raw('updated_at')]);

        $conReportes = DB::table('permisos_usuario')
            ->whereIn('modulo', ['reportes', 'reportes.resumen', 'reportes.stock', 'reportes.kardex'])
            ->distinct()->pluck('id_usuario');
        foreach ($conReportes as $id) {
            DB::table('permisos_usuario')->insertOrIgnore(['id_usuario' => $id, 'modulo' => 'reportes.ventas', 'created_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('permisos_usuario')->where('modulo', 'reportes.ventas')->delete();
        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'id_usuario_anula')) $table->dropConstrainedForeignId('id_usuario_anula');
            foreach (['monto_recibido', 'vuelto', 'numero_operacion', 'anulada_at', 'motivo_anulacion'] as $c) {
                if (Schema::hasColumn('ventas', $c)) $table->dropColumn($c);
            }
        });
    }
};
