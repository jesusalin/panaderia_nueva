<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── ROLES ───────────────────────────────────────────
        // El rol "admin" y el usuario admin/admin123 ya se crean automáticamente
        // en la migración 2025_01_01_000014_create_default_admin_user, así que
        // aquí solo garantizamos que exista el rol "usuario".
        if (!DB::table('roles')->where('nombre', 'usuario')->exists()) {
            DB::table('roles')->insert([
                'nombre'      => 'usuario',
                'descripcion' => 'Acceso según los módulos que le asigne el administrador',
            ]);
        }

        // ─── UNIDADES DE MEDIDA ──────────────────────────────
        DB::table('unidades_medida')->insert([
            ['nombre' => 'kilogramo', 'abreviatura' => 'kg'],
            ['nombre' => 'gramo',     'abreviatura' => 'g'],
            ['nombre' => 'litro',     'abreviatura' => 'L'],
            ['nombre' => 'mililitro', 'abreviatura' => 'mL'],
            ['nombre' => 'unidad',    'abreviatura' => 'und'],
            ['nombre' => 'bolsa',     'abreviatura' => 'bol'],
            ['nombre' => 'caja',      'abreviatura' => 'cja'],
        ]);

        // ─── USUARIOS DE PRUEBA ───────────────────────────────
        // El admin ya existe (creado por la migración). Aquí solo agregamos
        // usuarios de ejemplo para poder probar los permisos por módulo.
        $idRolUsuario = DB::table('roles')->where('nombre', 'usuario')->value('id');

        DB::table('usuarios')->insert([
            ['nombre'=>'Maria Quispe', 'apodo'=>'Ventas',    'usuario'=>'mquispe', 'email'=>'maria@panaderia.com',  'password'=>Hash::make('ventas123'), 'id_rol'=>$idRolUsuario,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Carlos Mamani','apodo'=>'Almacén',   'usuario'=>'cmamani', 'email'=>'carlos@panaderia.com', 'password'=>Hash::make('almacen123'),  'id_rol'=>$idRolUsuario,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── PERMISOS POR MÓDULO ─────────────────────────────
        // Maria (Ventas): módulos de venta y clientes
        // Carlos (Almacén): módulos de inventario, compras, producción y reportes
        DB::table('permisos_usuario')->insert([
            ['id_usuario' => 2, 'modulo' => 'ventas',     'created_at' => now()],
            ['id_usuario' => 2, 'modulo' => 'clientes',   'created_at' => now()],
            ['id_usuario' => 3, 'modulo' => 'inventario', 'created_at' => now()],
            ['id_usuario' => 3, 'modulo' => 'compras',    'created_at' => now()],
            ['id_usuario' => 3, 'modulo' => 'produccion', 'created_at' => now()],
            ['id_usuario' => 3, 'modulo' => 'reportes',   'created_at' => now()],
        ]);

        // ─── CATEGORÍAS ──────────────────────────────────────
        DB::table('categorias')->insert([
            ['nombre'=>'Panes',     'descripcion'=>'Panes y bollos del día',         'estado'=>'activo'],
            ['nombre'=>'Pasteles',  'descripcion'=>'Tortas, pasteles y tartas',       'estado'=>'activo'],
            ['nombre'=>'Galletas',  'descripcion'=>'Galletas y bizcochería',          'estado'=>'activo'],
            ['nombre'=>'Empanadas', 'descripcion'=>'Empanadas y pasteles salados',    'estado'=>'activo'],
            ['nombre'=>'Bebidas',   'descripcion'=>'Jugos, café y bebidas calientes', 'estado'=>'activo'],
        ]);

        // ─── PRODUCTOS ───────────────────────────────────────
        DB::table('productos')->insert([
            ['id_categoria'=>1,'nombre'=>'Pan Frances',          'descripcion'=>'Pan crujiente de masa simple',            'precio_venta'=>0.20, 'costo_produccion'=>0.10, 'stock_actual'=>150,'stock_minimo'=>50, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>1,'nombre'=>'Pan de Molde',         'descripcion'=>'Pan suave para sandwiches',               'precio_venta'=>4.50, 'costo_produccion'=>2.00, 'stock_actual'=>30, 'stock_minimo'=>10, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>1,'nombre'=>'Pan Integral',         'descripcion'=>'Pan con harina integral y semillas',      'precio_venta'=>5.00, 'costo_produccion'=>2.50, 'stock_actual'=>20, 'stock_minimo'=>10, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>1,'nombre'=>'Pan de Yema',          'descripcion'=>'Pan dulce con yema de huevo',             'precio_venta'=>0.50, 'costo_produccion'=>0.25, 'stock_actual'=>80, 'stock_minimo'=>30, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>2,'nombre'=>'Torta de Chocolate',   'descripcion'=>'Torta húmeda con cobertura de chocolate', 'precio_venta'=>45.00,'costo_produccion'=>20.00,'stock_actual'=>5,  'stock_minimo'=>2,  'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>2,'nombre'=>'Torta de Vainilla',    'descripcion'=>'Torta esponjosa con crema de vainilla',   'precio_venta'=>40.00,'costo_produccion'=>18.00,'stock_actual'=>4,  'stock_minimo'=>2,  'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>2,'nombre'=>'Pie de Manzana',       'descripcion'=>'Tarta de manzana con canela',             'precio_venta'=>25.00,'costo_produccion'=>10.00,'stock_actual'=>8,  'stock_minimo'=>3,  'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>3,'nombre'=>'Galletas de Avena',    'descripcion'=>'Galletas integrales con avena y pasas',   'precio_venta'=>3.50, 'costo_produccion'=>1.50, 'stock_actual'=>40, 'stock_minimo'=>15, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>3,'nombre'=>'Galletas de Mantequilla','descripcion'=>'Galletas suaves con mantequilla',       'precio_venta'=>4.00, 'costo_produccion'=>1.80, 'stock_actual'=>35, 'stock_minimo'=>15, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>4,'nombre'=>'Empanada de Pollo',    'descripcion'=>'Empanada rellena de pollo y verduras',    'precio_venta'=>2.50, 'costo_produccion'=>1.20, 'stock_actual'=>25, 'stock_minimo'=>10, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>4,'nombre'=>'Empanada de Queso',    'descripcion'=>'Empanada rellena de queso fresco',        'precio_venta'=>2.00, 'costo_produccion'=>0.90, 'stock_actual'=>30, 'stock_minimo'=>10, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>5,'nombre'=>'Café Americano',       'descripcion'=>'Café negro sin leche',                    'precio_venta'=>3.00, 'costo_produccion'=>0.80, 'stock_actual'=>100,'stock_minimo'=>20, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['id_categoria'=>5,'nombre'=>'Café con Leche',       'descripcion'=>'Café con leche fresca',                   'precio_venta'=>4.00, 'costo_produccion'=>1.20, 'stock_actual'=>100,'stock_minimo'=>20, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── MATERIA PRIMA ───────────────────────────────────
        DB::table('materia_prima')->insert([
            ['nombre'=>'Harina de Trigo',   'id_unidad'=>1,'stock_actual'=>50.000, 'stock_minimo'=>10.000,'costo_unitario'=>2.50, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Azúcar Blanca',     'id_unidad'=>1,'stock_actual'=>20.000, 'stock_minimo'=>5.000, 'costo_unitario'=>2.00, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Mantequilla',       'id_unidad'=>1,'stock_actual'=>10.000, 'stock_minimo'=>2.000, 'costo_unitario'=>12.00,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Huevos',            'id_unidad'=>5,'stock_actual'=>120.000,'stock_minimo'=>30.000,'costo_unitario'=>0.50, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Leche Fresca',      'id_unidad'=>3,'stock_actual'=>15.000, 'stock_minimo'=>3.000, 'costo_unitario'=>3.50, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Levadura',          'id_unidad'=>2,'stock_actual'=>500.000,'stock_minimo'=>100.000,'costo_unitario'=>0.03,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Sal',               'id_unidad'=>1,'stock_actual'=>5.000,  'stock_minimo'=>1.000, 'costo_unitario'=>1.00, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Cacao en Polvo',    'id_unidad'=>2,'stock_actual'=>800.000,'stock_minimo'=>200.000,'costo_unitario'=>0.05,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Aceite Vegetal',    'id_unidad'=>3,'stock_actual'=>8.000,  'stock_minimo'=>2.000, 'costo_unitario'=>5.00, 'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Café Molido',       'id_unidad'=>2,'stock_actual'=>2000.000,'stock_minimo'=>500.000,'costo_unitario'=>0.04,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── PROVEEDORES ─────────────────────────────────────
        DB::table('proveedores')->insert([
            ['nombre'=>'Molino San Jorge',      'ruc'=>'20123456789','direccion'=>'Av. Industrial 234, Lima',   'telefono'=>'01-234-5678','email'=>'ventas@molinosanjorge.com','contacto'=>'Roberto Sánchez','estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Lácteos del Sur',       'ruc'=>'20987654321','direccion'=>'Jr. Los Lácteos 456, Lima', 'telefono'=>'01-876-5432','email'=>'info@lacteossur.com',      'contacto'=>'Ana Torres',     'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Distribuidora El Huevo','ruc'=>'20456789123','direccion'=>'Calle Los Huevos 789, Lima','telefono'=>'01-456-7890','email'=>'pedidos@elhuevo.com',      'contacto'=>'Luis Pérez',     'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── CLIENTES ────────────────────────────────────────
        DB::table('clientes')->insert([
            ['nombre'=>'Cliente General', 'tipo'=>'particular','ruc'=>null,          'dni'=>null,        'telefono'=>null,         'email'=>null,                 'direccion'=>null,'distrito'=>null,      'referencia'=>null,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Juan Flores',     'tipo'=>'particular','ruc'=>null,          'dni'=>'12345678',  'telefono'=>'987654321',  'email'=>'juan@gmail.com',     'direccion'=>null,'distrito'=>'Chorrillos','referencia'=>null,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Rosa Mamani',     'tipo'=>'particular','ruc'=>null,          'dni'=>'87654321',  'telefono'=>'976543210',  'email'=>'rosa@gmail.com',     'direccion'=>null,'distrito'=>'Barranco', 'referencia'=>null,'estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Colegio San Luis','tipo'=>'colegio',   'ruc'=>'20111222333', 'dni'=>null,        'telefono'=>'01-567-8901','email'=>'compras@sanluis.edu','direccion'=>'Av. Los Alumnos 123','distrito'=>'Chorrillos','referencia'=>'Frente al parque','estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
            ['nombre'=>'Bodega La Esquina','tipo'=>'bodega',   'ruc'=>null,          'dni'=>'45678901',  'telefono'=>'998877665',  'email'=>null,                 'direccion'=>'Jr. Las Flores 456','distrito'=>'Chorrillos','referencia'=>'Esquina con Jr. Los Pinos','estado'=>'activo','created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── COMPRAS ─────────────────────────────────────────
        DB::table('compras')->insert([
            ['id_proveedor'=>1,'id_usuario'=>1,'numero_doc'=>'F001-00123','fecha_compra'=>now()->subDays(10),'subtotal'=>127.12,'igv'=>22.88,'total'=>150.00,'estado'=>'recibida', 'observaciones'=>'Compra mensual de harina',  'created_at'=>now(),'updated_at'=>now()],
            ['id_proveedor'=>2,'id_usuario'=>3,'numero_doc'=>'F002-00456','fecha_compra'=>now()->subDays(5), 'subtotal'=>59.32, 'igv'=>10.68,'total'=>70.00, 'estado'=>'recibida', 'observaciones'=>'Leche y mantequilla',        'created_at'=>now(),'updated_at'=>now()],
            ['id_proveedor'=>3,'id_usuario'=>3,'numero_doc'=>'F003-00789','fecha_compra'=>now()->subDays(2), 'subtotal'=>25.42, 'igv'=>4.58, 'total'=>30.00, 'estado'=>'pendiente','observaciones'=>'Pedido de huevos semanal',   'created_at'=>now(),'updated_at'=>now()],
        ]);

        DB::table('compra_detalles')->insert([
            ['id_compra'=>1,'id_materia'=>1,'cantidad'=>50.000,'precio_unitario'=>2.50,'subtotal'=>125.00,'created_at'=>now(),'updated_at'=>now()],
            ['id_compra'=>1,'id_materia'=>7,'cantidad'=>5.000, 'precio_unitario'=>1.00,'subtotal'=>5.00,  'created_at'=>now(),'updated_at'=>now()],
            ['id_compra'=>2,'id_materia'=>5,'cantidad'=>10.000,'precio_unitario'=>3.50,'subtotal'=>35.00, 'created_at'=>now(),'updated_at'=>now()],
            ['id_compra'=>2,'id_materia'=>3,'cantidad'=>2.500, 'precio_unitario'=>12.00,'subtotal'=>30.00,'created_at'=>now(),'updated_at'=>now()],
            ['id_compra'=>3,'id_materia'=>4,'cantidad'=>60.000,'precio_unitario'=>0.50,'subtotal'=>30.00, 'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── VENTAS ──────────────────────────────────────────
        DB::table('ventas')->insert([
            ['id_usuario'=>2,'id_cliente'=>1,'numero_venta'=>'V-000001','fecha_venta'=>now()->subDays(3),'subtotal'=>8.47, 'igv'=>1.53,'total'=>10.00,'tipo_pago'=>'efectivo','estado'=>'completada','observaciones'=>null,             'created_at'=>now(),'updated_at'=>now()],
            ['id_usuario'=>2,'id_cliente'=>2,'numero_venta'=>'V-000002','fecha_venta'=>now()->subDays(2),'subtotal'=>42.37,'igv'=>7.63,'total'=>50.00,'tipo_pago'=>'yape',    'estado'=>'completada','observaciones'=>null,             'created_at'=>now(),'updated_at'=>now()],
            ['id_usuario'=>1,'id_cliente'=>4,'numero_venta'=>'V-000003','fecha_venta'=>now()->subDays(1),'subtotal'=>84.75,'igv'=>15.25,'total'=>100.00,'tipo_pago'=>'efectivo','estado'=>'completada','observaciones'=>'Pedido colegio','created_at'=>now(),'updated_at'=>now()],
            ['id_usuario'=>2,'id_cliente'=>3,'numero_venta'=>'V-000004','fecha_venta'=>now(),            'subtotal'=>21.19,'igv'=>3.81,'total'=>25.00,'tipo_pago'=>'tarjeta', 'estado'=>'completada','observaciones'=>null,             'created_at'=>now(),'updated_at'=>now()],
        ]);

        DB::table('venta_detalles')->insert([
            ['id_venta'=>1,'id_producto'=>1, 'cantidad'=>20,'precio_unitario'=>0.20,'subtotal'=>4.00, 'created_at'=>now(),'updated_at'=>now()],
            ['id_venta'=>1,'id_producto'=>4, 'cantidad'=>10,'precio_unitario'=>0.50,'subtotal'=>5.00, 'created_at'=>now(),'updated_at'=>now()],
            ['id_venta'=>2,'id_producto'=>5, 'cantidad'=>1, 'precio_unitario'=>45.00,'subtotal'=>45.00,'created_at'=>now(),'updated_at'=>now()],
            ['id_venta'=>3,'id_producto'=>1, 'cantidad'=>100,'precio_unitario'=>0.20,'subtotal'=>20.00,'created_at'=>now(),'updated_at'=>now()],
            ['id_venta'=>3,'id_producto'=>10,'cantidad'=>20,'precio_unitario'=>2.50,'subtotal'=>50.00,'created_at'=>now(),'updated_at'=>now()],
            ['id_venta'=>4,'id_producto'=>12,'cantidad'=>5, 'precio_unitario'=>3.00,'subtotal'=>15.00,'created_at'=>now(),'updated_at'=>now()],
            ['id_venta'=>4,'id_producto'=>8, 'cantidad'=>2, 'precio_unitario'=>3.50,'subtotal'=>7.00, 'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── RECETAS ─────────────────────────────────────────
        DB::table('recetas')->insert([
            ['id_producto'=>1,'rendimiento'=>50,'descripcion'=>'Receta para 50 panes franceses',    'created_at'=>now(),'updated_at'=>now()],
            ['id_producto'=>5,'rendimiento'=>1, 'descripcion'=>'Receta para 1 torta de chocolate',  'created_at'=>now(),'updated_at'=>now()],
            ['id_producto'=>8,'rendimiento'=>20,'descripcion'=>'Receta para 20 galletas de avena',  'created_at'=>now(),'updated_at'=>now()],
        ]);

        DB::table('receta_detalles')->insert([
            ['id_receta'=>1,'id_materia'=>1,'cantidad'=>1.000, 'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>1,'id_materia'=>6,'cantidad'=>10.000,'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>1,'id_materia'=>7,'cantidad'=>20.000,'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>2,'id_materia'=>1,'cantidad'=>0.500, 'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>2,'id_materia'=>2,'cantidad'=>0.300, 'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>2,'id_materia'=>3,'cantidad'=>0.200, 'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>2,'id_materia'=>4,'cantidad'=>4.000, 'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>2,'id_materia'=>8,'cantidad'=>100.000,'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>3,'id_materia'=>1,'cantidad'=>0.200, 'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>3,'id_materia'=>2,'cantidad'=>0.150, 'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>3,'id_materia'=>3,'cantidad'=>0.100, 'created_at'=>now(),'updated_at'=>now()],
            ['id_receta'=>3,'id_materia'=>4,'cantidad'=>1.000, 'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── PRODUCCIONES ────────────────────────────────────
        DB::table('producciones')->insert([
            ['id_producto'=>1,'id_usuario'=>1,'cantidad'=>100,'fecha'=>now()->subDays(2),'observacion'=>'Producción matutina',      'created_at'=>now(),'updated_at'=>now()],
            ['id_producto'=>5,'id_usuario'=>1,'cantidad'=>3,  'fecha'=>now()->subDays(1),'observacion'=>'Tortas para fin de semana','created_at'=>now(),'updated_at'=>now()],
            ['id_producto'=>8,'id_usuario'=>1,'cantidad'=>40, 'fecha'=>now(),            'observacion'=>'Galletas del día',          'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ─── KARDEX DE PRODUCTOS (datos de prueba) ───────────
        DB::table('kardex_productos')->insert([
            ['id_producto'=>1,'id_usuario'=>1,'tipo'=>'entrada','motivo'=>'produccion','referencia_id'=>1,'cantidad'=>100,'stock_antes'=>50, 'stock_despues'=>150,'observacion'=>'Producción #1','created_at'=>now()->subDays(2)],
            ['id_producto'=>5,'id_usuario'=>1,'tipo'=>'entrada','motivo'=>'produccion','referencia_id'=>2,'cantidad'=>3,  'stock_antes'=>2,  'stock_despues'=>5,  'observacion'=>'Producción #2','created_at'=>now()->subDays(1)],
            ['id_producto'=>8,'id_usuario'=>1,'tipo'=>'entrada','motivo'=>'produccion','referencia_id'=>3,'cantidad'=>40, 'stock_antes'=>0,  'stock_despues'=>40, 'observacion'=>'Producción #3','created_at'=>now()],
            ['id_producto'=>1,'id_usuario'=>2,'tipo'=>'salida', 'motivo'=>'venta',     'referencia_id'=>1,'cantidad'=>20, 'stock_antes'=>150,'stock_despues'=>130,'observacion'=>'Venta V-000001','created_at'=>now()->subDays(3)],
            ['id_producto'=>4,'id_usuario'=>2,'tipo'=>'salida', 'motivo'=>'venta',     'referencia_id'=>1,'cantidad'=>10, 'stock_antes'=>80, 'stock_despues'=>70, 'observacion'=>'Venta V-000001','created_at'=>now()->subDays(3)],
            ['id_producto'=>5,'id_usuario'=>2,'tipo'=>'salida', 'motivo'=>'venta',     'referencia_id'=>2,'cantidad'=>1,  'stock_antes'=>5,  'stock_despues'=>4,  'observacion'=>'Venta V-000002','created_at'=>now()->subDays(2)],
            ['id_producto'=>1,'id_usuario'=>1,'tipo'=>'salida', 'motivo'=>'venta',     'referencia_id'=>3,'cantidad'=>100,'stock_antes'=>130,'stock_despues'=>30, 'observacion'=>'Venta V-000003','created_at'=>now()->subDays(1)],
            ['id_producto'=>12,'id_usuario'=>2,'tipo'=>'salida','motivo'=>'venta',     'referencia_id'=>4,'cantidad'=>5,  'stock_antes'=>100,'stock_despues'=>95, 'observacion'=>'Venta V-000004','created_at'=>now()],
        ]);
    }
}
