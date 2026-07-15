<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reduce el sistema de roles a solo 'admin' y 'usuario'.
     * El acceso granular por módulo ahora se maneja en la tabla
     * permisos_usuario (ver migración 2025_01_01_000013).
     */
    public function up(): void
    {
        // Crea el rol "usuario" si todavía no existe
        $idUsuario = DB::table('roles')->where('nombre', 'usuario')->value('id');
        if (!$idUsuario) {
            $idUsuario = DB::table('roles')->insertGetId([
                'nombre'      => 'usuario',
                'descripcion' => 'Acceso según los módulos que le asigne el administrador',
            ]);
        }

        // Reasigna a quienes tenían roles antiguos (vendedor, almacenero) al rol "usuario"
        $rolesViejos = DB::table('roles')->whereIn('nombre', ['vendedor', 'almacenero'])->pluck('id');

        if ($rolesViejos->isNotEmpty()) {
            DB::table('usuarios')->whereIn('id_rol', $rolesViejos)->update(['id_rol' => $idUsuario]);
            DB::table('roles')->whereIn('id', $rolesViejos)->delete();
        }
    }

    public function down(): void
    {
        // No se revierte: los roles antiguos no se pueden reconstruir de forma confiable.
    }
};
