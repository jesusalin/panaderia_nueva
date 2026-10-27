<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina las tablas del antiguo módulo "Tiempos de Operación".
 *
 * Los tiempos de la investigación (TPR, TCS, TCI y PER) se miden por observación
 * directa con la ficha del Anexo 3, no con el sistema, así que este módulo y sus
 * mediciones simuladas ya no forman parte de la solución.
 *
 * Es seguro en bases nuevas y en bases ya migradas: dropIfExists no falla si la
 * tabla no existe. Las migraciones que las crearon se conservan para no alterar
 * el historial de migraciones de las bases existentes.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('tiempos_operacion');
        Schema::dropIfExists('tiempos_baseline');
    }

    public function down(): void
    {
        // No se recrean: el módulo fue retirado del sistema.
    }
};
