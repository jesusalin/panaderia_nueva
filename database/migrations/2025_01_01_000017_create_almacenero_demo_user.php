<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Crea un usuario de demostración "almacenero" (contraseña: almacenero123)
     * con los módulos de inventario asignados, para mostrar en la sustentación
     * cómo se ve el sistema con un usuario que no es admin.
     */
    public function up(): void
    {
        // El rol "usuario" ya se crea en la migración de simplificación de roles;
        // si por algún motivo no existe, lo creamos aquí también.
        $idUsuario = DB::table('roles')->where('nombre', 'usuario')->value('id');
        if (!$idUsuario) {
            $idUsuario = DB::table('roles')->insertGetId([
                'nombre'      => 'usuario',
                'descripcion' => 'Acceso según los módulos que le asigne el administrador',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $idAlmacenero = DB::table('usuarios')->where('usuario', 'almacenero')->value('id');

        if (!$idAlmacenero) {
            $idAlmacenero = DB::table('usuarios')->insertGetId([
                'nombre'     => 'Almacenero Demo',
                'apodo'      => 'Almacén',
                'usuario'    => 'almacenero',
                'email'      => 'almacenero@panaderia.com',
                'password'   => Hash::make('almacenero123'),
                'id_rol'     => $idUsuario,
                'estado'     => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Le asigna los módulos típicos de un almacenero, sin duplicar si ya los tuviera
        $modulos = ['inventario', 'compras', 'produccion', 'reportes'];
        foreach ($modulos as $modulo) {
            $yaTiene = DB::table('permisos_usuario')
                ->where('id_usuario', $idAlmacenero)->where('modulo', $modulo)->exists();
            if (!$yaTiene) {
                DB::table('permisos_usuario')->insert([
                    'id_usuario' => $idAlmacenero,
                    'modulo'     => $modulo,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No se elimina al revertir, para no romper una demo ya preparada.
    }
};
