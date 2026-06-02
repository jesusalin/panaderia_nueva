<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['nombre' => 'admin',      'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'vendedor',   'descripcion' => 'Puede registrar ventas y ver reportes básicos'],
            ['nombre' => 'almacenero', 'descripcion' => 'Gestiona inventario y compras'],
        ]);

        DB::table('unidades_medida')->insert([
            ['nombre' => 'kilogramo', 'abreviatura' => 'kg'],
            ['nombre' => 'gramo',     'abreviatura' => 'g'],
            ['nombre' => 'litro',     'abreviatura' => 'L'],
            ['nombre' => 'mililitro', 'abreviatura' => 'mL'],
            ['nombre' => 'unidad',    'abreviatura' => 'und'],
            ['nombre' => 'bolsa',     'abreviatura' => 'bol'],
            ['nombre' => 'caja',      'abreviatura' => 'cja'],
        ]);

        DB::table('usuarios')->insert([
            [
                'nombre'   => 'Administrador',
                'usuario'  => 'admin',
                'email'    => 'admin@panaderia.com',
                'password' => Hash::make('admin123'),
                'id_rol'   => 1,
                'estado'   => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        DB::table('categorias')->insert([
            ['nombre' => 'Panes',     'descripcion' => 'Panes y bollos del día'],
            ['nombre' => 'Pasteles',  'descripcion' => 'Tortas, pasteles y tartas'],
            ['nombre' => 'Galletas',  'descripcion' => 'Galletas y bizcochería'],
            ['nombre' => 'Empanadas', 'descripcion' => 'Empanadas y pasteles salados'],
            ['nombre' => 'Bebidas',   'descripcion' => 'Jugos, café y bebidas calientes'],
        ]);

        DB::table('clientes')->insert([
            ['nombre' => 'Cliente General', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
