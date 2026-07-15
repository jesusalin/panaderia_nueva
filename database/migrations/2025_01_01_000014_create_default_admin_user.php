<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Garantiza que exista un usuario administrador por defecto
     * (usuario: admin / contraseña: admin123) sin depender de que
     * se ejecute el DatabaseSeeder. Así el sistema siempre tiene
     * a alguien que pueda entrar y crear al resto de usuarios.
     */
    public function up(): void
    {
        // Asegura que exista el rol "admin"
        $idAdmin = DB::table('roles')->where('nombre', 'admin')->value('id');
        if (!$idAdmin) {
            $idAdmin = DB::table('roles')->insertGetId([
                'nombre'      => 'admin',
                'descripcion' => 'Acceso total al sistema',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Crea el usuario admin solo si todavía no existe
        $existeAdmin = DB::table('usuarios')->where('usuario', 'admin')->exists();
        if (!$existeAdmin) {
            DB::table('usuarios')->insert([
                'nombre'     => 'Administrador',
                'apodo'      => null,
                'usuario'    => 'admin',
                'email'      => 'admin@panaderia.com',
                'password'   => Hash::make('admin123'),
                'id_rol'     => $idAdmin,
                'estado'     => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No se elimina el usuario admin al revertir, para no dejar
        // el sistema sin nadie que pueda ingresar.
    }
};
