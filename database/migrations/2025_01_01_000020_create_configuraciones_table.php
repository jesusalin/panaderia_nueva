<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ajustes generales del sistema (clave => valor JSON). El primero es
        // "orden_menu": el orden del menú lateral que define el admin y que ven
        // todos los usuarios.
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->json('valor')->nullable();
            $table->timestamps();
        });

        // Antes el orden era por usuario (migración 000019). Si un admin ya había
        // ordenado su menú, ese orden pasa a ser el orden general, y la columna
        // por usuario se elimina.
        if (Schema::hasColumn('usuarios', 'orden_menu')) {
            $ordenAdmin = DB::table('usuarios')
                ->join('roles', 'roles.id', '=', 'usuarios.id_rol')
                ->where('roles.nombre', 'admin')
                ->whereNotNull('usuarios.orden_menu')
                ->value('usuarios.orden_menu');

            if ($ordenAdmin) {
                DB::table('configuraciones')->insert([
                    'clave'      => 'orden_menu',
                    'valor'      => $ordenAdmin,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('orden_menu');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');

        if (!Schema::hasColumn('usuarios', 'orden_menu')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->json('orden_menu')->nullable()->after('ultimo_acceso');
            });
        }
    }
};
