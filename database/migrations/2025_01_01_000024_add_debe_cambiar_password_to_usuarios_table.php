<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuando el admin le pone una contraseña temporal a alguien (porque la olvidó),
 * esta marca obliga a esa persona a elegir su propia contraseña al entrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'debe_cambiar_password')) {
                $table->boolean('debe_cambiar_password')->default(false)->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('debe_cambiar_password');
        });
    }
};
