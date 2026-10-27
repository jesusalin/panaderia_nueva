<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de ejemplo de la Panadería Muruwasi (Chorrillos).
 *
 * En vez de escribir stocks y movimientos "a mano" (que nunca cuadraban), este
 * seeder SIMULA las últimas 2 semanas de operación, día por día y en orden:
 *
 *   04:30  producción del día (descuenta insumos según la receta, sube productos)
 *   08:00  las órdenes automáticas de la noche anterior se convierten en compra
 *   11:00  llega el pedido: la compra se recibe y entra al stock
 *   06:45  pedidos fijos de clientes (bodega, colegio, restaurante) y ventas del mostrador
 *   17:00  mermas ocasionales (huevos rotos, leche vencida…)
 *   19:30  conteo físico de los 10 insumos de control (dos veces) con su ajuste
 *   21:00  revisión de stock bajo: orden automática para cada insumo en su mínimo
 *
 * Quedan listos para la demostración: órdenes pendientes (con y sin proveedor),
 * una orden descartada, una compra registrada que aún no llega, insumos y un
 * producto bajo su mínimo, y dos conteos físicos con diferencias.
 *
 * Cada operación se registra EXACTAMENTE como lo hace el sistema (mismo IGV, mismo
 * número de venta, mismas observaciones, mismo cálculo de receta), así que:
 *   - el stock final de cada insumo y producto = la suma de sus movimientos,
 *   - el Reporte de Kardex siempre dice "el kardex cuadra",
 *   - los totales de compras y ventas coinciden con sus detalles.
 *
 * Es reproducible (semilla fija): siempre genera los mismos datos, con fechas
 * relativas a HOY (solo hasta la hora actual).
 */
class DatabaseSeeder extends Seeder
{
    /** Días simulados (el último es hoy). */
    private const DIAS = 14;

    /** Los 10 insumos fijos del conteo físico (sección 5 de la ficha / PER). */
    private const INSUMOS_CONTEO = [
        'harina', 'azucar', 'sal', 'levadura', 'manteca',
        'mantequilla', 'huevos', 'leche', 'aceite', 'margarina',
    ];

    private Carbon $ahora;
    private Carbon $cursor;              // último instante registrado (orden cronológico estricto)
    private array $u = [];               // usuario => id
    private array $mp = [];              // código => [id, nombre, unidad, proveedor, ...]
    private array $pr = [];              // código => [id, nombre, precio, ...]
    private array $recetas = [];         // código producto => [rend, [código insumo => cantidad]]
    private array $stockMp = [];         // código => float
    private array $costoMp = [];         // código => último precio
    private array $stockPr = [];         // código => int
    private array $movimientos = [];     // filas de movimientos_inventario (en orden)
    private array $kardex = [];          // filas de kardex_productos (en orden)
    private array $ordenPendiente = [];  // código => id de orden que queda pendiente
    private array $porConvertir = [];    // código => id de orden generada anoche (se convierte hoy)
    private array $porRecibir = [];      // compras ya hechas que llegan en la próxima recepción
    private bool  $hayDescartada = false;
    private array $clientes = [];
    private array $docProveedor = [];    // código proveedor => correlativo de factura

    public function run(): void
    {
        mt_srand(2026);
        $this->ahora  = now();
        $this->cursor = now()->subDays(self::DIAS + 1)->startOfDay();

        $this->rolesUnidadesUsuarios();
        $this->catalogo();
        $this->simular();
        $this->guardarSaldos();
    }

    // ═══════════════════════════════════════════════════════════════════
    // ROLES, UNIDADES, USUARIOS Y PERMISOS
    // ═══════════════════════════════════════════════════════════════════
    private function rolesUnidadesUsuarios(): void
    {
        // El rol "admin" y el usuario admin/admin123 se crean en la migración 000014;
        // el usuario "almacenero" (almacenero123) en la 000017. Aquí solo garantizamos
        // el rol "usuario" y agregamos dos usuarios más de ejemplo.
        if (!DB::table('roles')->where('nombre', 'usuario')->exists()) {
            DB::table('roles')->insert([
                'nombre'      => 'usuario',
                'descripcion' => 'Acceso según los permisos que le asigne el administrador',
            ]);
        }

        DB::table('unidades_medida')->insert([
            ['nombre' => 'kilogramo', 'abreviatura' => 'kg'],
            ['nombre' => 'gramo',     'abreviatura' => 'g'],
            ['nombre' => 'litro',     'abreviatura' => 'L'],
            ['nombre' => 'mililitro', 'abreviatura' => 'mL'],
            ['nombre' => 'unidad',    'abreviatura' => 'und'],
            ['nombre' => 'bolsa',     'abreviatura' => 'bol'],
            ['nombre' => 'caja',      'abreviatura' => 'cja'],
        ]);

        $idRolUsuario = DB::table('roles')->where('nombre', 'usuario')->value('id');
        $alta = $this->ahora->copy()->subDays(self::DIAS + 5);
        DB::table('usuarios')->insert([
            ['nombre' => 'Maria Quispe',  'apodo' => 'Ventas',  'usuario' => 'mquispe', 'email' => 'maria@panaderia.com',
             'password' => Hash::make('ventas123'),  'id_rol' => $idRolUsuario, 'estado' => 'activo', 'created_at' => $alta, 'updated_at' => $alta],
            ['nombre' => 'Carlos Mamani', 'apodo' => 'Almacén', 'usuario' => 'cmamani', 'email' => 'carlos@panaderia.com',
             'password' => Hash::make('almacen123'), 'id_rol' => $idRolUsuario, 'estado' => 'activo', 'created_at' => $alta, 'updated_at' => $alta],
        ]);

        foreach (['admin', 'almacenero', 'mquispe', 'cmamani'] as $usuario) {
            $this->u[$usuario] = DB::table('usuarios')->where('usuario', $usuario)->value('id');
        }

        // Permisos por pantalla (ver Usuario::PERMISOS). El almacenero ya trae los
        // suyos desde la migración 000017 (convertidos a pantallas por la 000021).
        $permisosDemo = [
            // Maria (Ventas): vende desde el carrito de Productos y atiende clientes
            'mquispe' => ['ventas', 'clientes', 'catalogo.productos'],
            // Carlos (Almacén): inventario, compras, producción y reportes de stock/kardex
            'cmamani' => [
                'inventario.materia_prima', 'inventario.movimientos', 'inventario.conteo', 'inventario.kardex',
                'compras.proveedores', 'compras.compras', 'compras.ordenes',
                'produccion.produccion', 'produccion.recetas',
                'reportes.resumen', 'reportes.stock', 'reportes.kardex',
            ],
        ];
        foreach ($permisosDemo as $usuario => $claves) {
            foreach ($claves as $clave) {
                DB::table('permisos_usuario')->insertOrIgnore([
                    'id_usuario' => $this->u[$usuario], 'modulo' => $clave, 'created_at' => $alta,
                ]);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // CATÁLOGO: categorías, proveedores, insumos, productos, recetas, clientes
    // ═══════════════════════════════════════════════════════════════════
    private function catalogo(): void
    {
        $alta = $this->ahora->copy()->subDays(self::DIAS)->startOfDay();
        $ts = ['created_at' => $alta, 'updated_at' => $alta];
        $unidad = DB::table('unidades_medida')->pluck('id', 'abreviatura');

        // ── Categorías ──
        $categorias = [
            'panes'     => ['Panes', 'Panes del día: francés, ciabatta, yema, chancay, integral y molde'],
            'pasteleria'=> ['Pastelería', 'Tortas y kekes enteros'],
            'dulces'    => ['Galletas y dulces', 'Alfajores y galletas por unidad'],
            'salados'   => ['Salados', 'Empanadas horneadas'],
        ];
        $idCat = [];
        foreach ($categorias as $c => [$nombre, $desc]) {
            $idCat[$c] = DB::table('categorias')->insertGetId(['nombre' => $nombre, 'descripcion' => $desc, 'estado' => 'activo'] + $ts);
        }

        // ── Proveedores (nombres y RUC de ejemplo) ──
        $proveedores = [
            'molino'   => ['Molinos del Pacífico S.A.C.',        '20601234561', 'Av. Defensores del Morro 1520, Chorrillos', '01-251-4400', 'ventas@molinospacifico.pe',  'Roberto Sánchez'],
            'lacteos'  => ['Distribuidora Lácteos Andinos E.I.R.L.', '20602345672', 'Jr. Sáenz Peña 318, Barranco',            '01-247-8812', 'pedidos@lacteosandinos.pe',  'Ana Torres'],
            'granja'   => ['Granja Avícola Los Pinos S.R.L.',     '20603456783', 'Carretera Panamericana Sur km 32, Lurín',   '01-430-1290', 'ventas@granjalospinos.pe',   'Luis Pérez'],
            'insumos'  => ['Insumos Panaderos Lima S.A.C.',       '20604567894', 'Av. Huaylas 2245, Chorrillos',             '01-254-6603', 'atencion@insumospanaderos.pe','Carmen Rojas'],
            'mercado'  => ['Mercado Mayorista Santa Anita – Puesto 48', '10456789012', 'Av. La Cultura s/n, Santa Anita',      '987 654 321', null,                         'Don Julio Huamán'],
        ];
        $idProv = [];
        foreach ($proveedores as $c => [$nombre, $ruc, $dir, $tel, $email, $contacto]) {
            $idProv[$c] = DB::table('proveedores')->insertGetId([
                'nombre' => $nombre, 'ruc' => $ruc, 'direccion' => $dir, 'telefono' => $tel,
                'email' => $email, 'contacto' => $contacto, 'estado' => 'activo',
            ] + $ts);
            $this->docProveedor[$c] = 100 + mt_rand(1, 400);
        }

        // ── Insumos (33): código => [nombre, unidad, proveedor, costo S/ por unidad, presentación de compra] ──
        // La "presentación" es cómo se compra (saco de 50 kg, jaba de 180 huevos…):
        // la cantidad de reposición siempre es un múltiplo de ella.
        // "esencia" se deja SIN proveedor a propósito: muestra la validación de
        // Órdenes Automáticas cuando un insumo no tiene a quién comprarle.
        $insumos = [
            'harina'      => ['Harina de trigo panadera', 'kg',  'molino',  3.20, 50],
            'harina_int'  => ['Harina integral',          'kg',  'molino',  4.50, 5],
            'harina_pas'  => ['Harina pastelera',         'kg',  'molino',  3.80, 25],
            'azucar'      => ['Azúcar blanca',            'kg',  'molino',  3.60, 50],
            'azucar_rub'  => ['Azúcar rubia',             'kg',  'molino',  3.40, 5],
            'sal'         => ['Sal de mesa',              'kg',  'molino',  1.20, 5],
            'maicena'     => ['Maicena',                  'kg',  'molino',  7.50, 1],
            'avena'       => ['Avena en hojuelas',        'kg',  'molino',  6.50, 2],
            'levadura'    => ['Levadura seca instantánea','kg',  'insumos', 22.00, 0.5],
            'mejorador'   => ['Mejorador de masa',        'kg',  'insumos', 18.00, 1],
            'manteca'     => ['Manteca vegetal',          'kg',  'insumos',  8.50, 5],
            'margarina'   => ['Margarina',                'kg',  'insumos',  9.50, 5],
            'polvo'       => ['Polvo de hornear',         'kg',  'insumos', 14.00, 0.5],
            'cacao'       => ['Cacao en polvo',           'kg',  'insumos', 25.00, 1],
            'chocolate'   => ['Chocolate de cobertura',   'kg',  'insumos', 32.00, 1],
            'esencia'     => ['Esencia de vainilla',      'mL',  null,       0.08, 500],
            'ajonjoli'    => ['Ajonjolí',                 'kg',  'insumos', 16.00, 1],
            'anis'        => ['Anís en grano',            'kg',  'insumos', 30.00, 0.5],
            'canela'      => ['Canela molida',            'kg',  'insumos', 40.00, 0.25],
            'pasas'       => ['Pasas',                    'kg',  'insumos', 15.00, 1],
            'mantequilla' => ['Mantequilla',              'kg',  'lacteos', 28.00, 1],
            'leche'       => ['Leche fresca',             'L',   'lacteos',  4.20, 10],
            'leche_evap'  => ['Leche evaporada (lata)',   'und', 'lacteos',  4.30, 12],
            'leche_cond'  => ['Leche condensada (lata)',  'und', 'lacteos',  5.80, 12],
            'crema'       => ['Crema de leche',           'L',   'lacteos', 14.00, 2],
            'manjar'      => ['Manjar blanco',            'kg',  'lacteos', 12.00, 2],
            'huevos'      => ['Huevos',                   'und', 'granja',   0.55, 180],
            'pollo'       => ['Pechuga de pollo',         'kg',  'granja',  16.00, 3],
            'carne'       => ['Carne molida de res',      'kg',  'mercado', 26.00, 3],
            'cebolla'     => ['Cebolla roja',             'kg',  'mercado',  3.20, 5],
            'aceitunas'   => ['Aceitunas de botija',      'kg',  'mercado', 18.00, 1],
            'naranjas'    => ['Naranjas',                 'kg',  'mercado',  3.50, 5],
            'aceite'      => ['Aceite vegetal',           'L',   'mercado',  9.00, 5],
        ];

        // ── Productos: código => [categoría, nombre, descripción, precio, stock mínimo, objetivo, demanda diaria] ──
        // "objetivo": hasta cuánto se produce cada mañana; "demanda": venta diaria
        // aproximada (con ella se calculan el stock mínimo y la reposición de insumos).
        $productos = [
            'frances'   => ['panes',      'Pan francés',          'Pan crocante de masa simple',               0.20,  40, 480, 330],
            'ciabatta'  => ['panes',      'Pan ciabatta',         'Pan rústico con aceite de oliva',           0.40,  10,  90,  65],
            'yema'      => ['panes',      'Pan de yema',          'Pan dulce y suave con yema de huevo',       0.30,  10, 100,  55],
            'chancay'   => ['panes',      'Pan chancay',          'Pan dulce con anís, típico de Chancay',     0.40,   4,  20,  10],
            'integral'  => ['panes',      'Pan integral',         'Pan de harina integral con ajonjolí',       0.40,   3,  15,   8],
            'molde'     => ['panes',      'Pan de molde',         'Pan de molde blanco (500 g)',               8.50,   3,   8,   3],
            'torta_choc'=> ['pasteleria', 'Torta de chocolate',   'Torta húmeda con cobertura de chocolate',  55.00,   1,   3,   1],
            'tres_leches'=>['pasteleria', 'Torta tres leches',    'Bizcocho bañado en tres leches',           60.00,   2,   3,   1],
            'keke'      => ['pasteleria', 'Keke de naranja',      'Keke casero de naranja con pasas',         18.00,   1,   4,   1],
            'alfajor'   => ['dulces',     'Alfajor de maicena',   'Alfajor relleno de manjar blanco',          1.50,  15,  30,   8],
            'galleta'   => ['dulces',     'Galleta de avena',     'Galleta de avena con pasas y canela',       1.00,  10,  25,   6],
            'emp_carne' => ['salados',    'Empanada de carne',    'Empanada horneada de carne con aceituna',   4.00,   6,  20,   6],
            'emp_pollo' => ['salados',    'Empanada de pollo',    'Empanada horneada de pollo',                4.00,   6,  20,   6],
        ];

        // ── Recetas: producto => [rendimiento, [insumo => cantidad por receta]] ──
        $this->recetas = [
            'frances'    => [100, ['harina' => 3.000, 'sal' => 0.060, 'levadura' => 0.030, 'mejorador' => 0.030, 'manteca' => 0.060, 'azucar' => 0.030]],
            'ciabatta'   => [50,  ['harina' => 2.500, 'sal' => 0.050, 'levadura' => 0.025, 'aceite' => 0.100]],
            'yema'       => [60,  ['harina' => 2.000, 'azucar' => 0.300, 'huevos' => 6, 'mantequilla' => 0.200, 'levadura' => 0.030, 'leche' => 0.300, 'sal' => 0.030]],
            'chancay'    => [50,  ['harina' => 2.000, 'azucar' => 0.400, 'manteca' => 0.250, 'huevos' => 4, 'anis' => 0.020, 'levadura' => 0.030, 'sal' => 0.020]],
            'integral'   => [50,  ['harina_int' => 1.500, 'harina' => 0.500, 'levadura' => 0.025, 'sal' => 0.030, 'ajonjoli' => 0.050, 'azucar_rub' => 0.080, 'aceite' => 0.050]],
            'molde'      => [10,  ['harina' => 5.000, 'azucar' => 0.250, 'sal' => 0.100, 'levadura' => 0.080, 'margarina' => 0.300, 'leche' => 0.500, 'mejorador' => 0.040]],
            'torta_choc' => [1,   ['harina_pas' => 0.400, 'azucar' => 0.350, 'cacao' => 0.080, 'huevos' => 6, 'mantequilla' => 0.200, 'polvo' => 0.015, 'leche' => 0.250, 'chocolate' => 0.300, 'esencia' => 10]],
            'tres_leches'=> [1,   ['harina_pas' => 0.300, 'azucar' => 0.300, 'huevos' => 8, 'leche_evap' => 1, 'leche_cond' => 1, 'crema' => 0.250, 'esencia' => 10, 'polvo' => 0.010]],
            'keke'       => [2,   ['harina_pas' => 0.500, 'azucar' => 0.400, 'huevos' => 6, 'margarina' => 0.250, 'naranjas' => 0.500, 'polvo' => 0.020, 'pasas' => 0.100]],
            'alfajor'    => [40,  ['maicena' => 0.500, 'harina_pas' => 0.250, 'mantequilla' => 0.300, 'azucar' => 0.150, 'huevos' => 3, 'manjar' => 0.600, 'polvo' => 0.010]],
            'galleta'    => [50,  ['avena' => 0.600, 'harina_pas' => 0.400, 'azucar_rub' => 0.300, 'margarina' => 0.300, 'huevos' => 3, 'canela' => 0.010, 'pasas' => 0.150, 'polvo' => 0.010]],
            'emp_carne'  => [30,  ['harina' => 1.500, 'manteca' => 0.400, 'sal' => 0.020, 'carne' => 1.200, 'cebolla' => 1.000, 'aceitunas' => 0.200, 'huevos' => 4, 'aceite' => 0.100]],
            'emp_pollo'  => [30,  ['harina' => 1.500, 'manteca' => 0.400, 'sal' => 0.020, 'pollo' => 1.200, 'cebolla' => 0.800, 'aceitunas' => 0.150, 'huevos' => 4, 'aceite' => 0.100]],
        ];

        // Consumo diario estimado de cada insumo (según la demanda y las recetas) y lo
        // máximo que puede pedir la producción de un día (todas las tandas a la vez).
        //   stock mínimo = lo mayor entre 2 días de consumo y un día de producción completo
        //   reposición   = 6 días de consumo, redondeado a la presentación de compra
        $consumo = array_fill_keys(array_keys($insumos), 0.0);
        $maxDia  = array_fill_keys(array_keys($insumos), 0.0);
        foreach ($this->recetas as $p => [$rend, $items]) {
            foreach ($items as $i => $cant) {
                $consumo[$i] += $productos[$p][6] / $rend * $cant;
                $maxDia[$i]  += ceil($productos[$p][5] / $rend) * $cant;
            }
        }

        foreach ($insumos as $c => [$nombre, $abrev, $prov, $costo, $presentacion]) {
            $minimo     = $this->redondear(max(2 * $consumo[$c], $maxDia[$c]), $abrev);
            $reposicion = max(1, ceil(6 * $consumo[$c] / $presentacion)) * $presentacion;
            $inicial    = $this->redondear($minimo + (30 + mt_rand(0, 70)) / 100 * $reposicion, $abrev);

            $id = DB::table('materia_prima')->insertGetId([
                'nombre' => $nombre, 'id_unidad' => $unidad[$abrev],
                'id_proveedor' => $prov ? $idProv[$prov] : null,
                'stock_actual' => $inicial, 'stock_minimo' => $minimo,
                'cantidad_reposicion' => $prov ? $reposicion : null,
                'costo_unitario' => $costo, 'estado' => 'activo',
            ] + $ts);

            $this->mp[$c] = ['id' => $id, 'nombre' => $nombre, 'unidad' => $abrev, 'prov' => $prov,
                             'id_prov' => $prov ? $idProv[$prov] : null, 'minimo' => $minimo, 'repo' => $reposicion];
            $this->stockMp[$c] = $inicial;
            $this->costoMp[$c] = $costo;
        }

        foreach ($productos as $c => [$cat, $nombre, $desc, $precio, $minimo, $objetivo, $demanda]) {
            // Costo de producción = costo de los insumos de la receta / rendimiento
            [$rend, $items] = $this->recetas[$c];
            $costo = 0;
            foreach ($items as $i => $cant) $costo += $cant * $insumos[$i][3];
            $inicial = (int) round($objetivo * 0.6);

            $id = DB::table('productos')->insertGetId([
                'id_categoria' => $idCat[$cat], 'nombre' => $nombre, 'descripcion' => $desc,
                'precio_venta' => $precio, 'costo_produccion' => round($costo / $rend, 2),
                'stock_actual' => $inicial, 'stock_minimo' => $minimo, 'estado' => 'activo',
            ] + $ts);
            $this->pr[$c] = ['id' => $id, 'nombre' => $nombre, 'precio' => $precio, 'objetivo' => $objetivo];
            $this->stockPr[$c] = $inicial;
        }

        foreach ($this->recetas as $p => [$rend, $items]) {
            $idReceta = DB::table('recetas')->insertGetId([
                'id_producto' => $this->pr[$p]['id'], 'rendimiento' => $rend,
                'descripcion' => "Receta para {$rend} " . ($rend === 1 ? 'unidad' : 'unidades') . " de {$this->pr[$p]['nombre']}",
            ] + $ts);
            foreach ($items as $i => $cant) {
                DB::table('receta_detalles')->insert(['id_receta' => $idReceta, 'id_materia' => $this->mp[$i]['id'], 'cantidad' => $cant] + $ts);
            }
        }

        // ── Clientes (el id 1 debe ser "Cliente General": las ventas sin cliente usan ese) ──
        $clientes = [
            'general'  => ['Cliente General',          'particular',  null,          null,       null,         null,                      null,                    null,         null],
            'bodega'   => ['Bodega La Esquina',        'bodega',      null,          '45678901', '998 877 665', null,                     'Jr. Las Flores 456',    'Chorrillos', 'Esquina con Jr. Los Pinos'],
            'colegio'  => ['I.E.P. San Luis',          'colegio',     '20111222333', null,       '01-567-8901', 'compras@sanluis.edu.pe', 'Av. Los Alumnos 123',   'Chorrillos', 'Frente al parque'],
            'resto'    => ['Restaurante El Chorrillano','restaurante','20555666777', null,       '01-251-3344', 'pedidos@elchorrillano.pe','Malecón Grau 610',     'Chorrillos', 'Cerca del muelle de pescadores'],
            'juan'     => ['Juan Flores',              'particular',  null,          '12345678', '987 654 321', null,                     null,                    'Chorrillos', null],
            'rosa'     => ['Rosa Mamani',              'particular',  null,          '87654321', '976 543 210', null,                     null,                    'Barranco',   null],
        ];
        foreach ($clientes as $c => [$nombre, $tipo, $ruc, $dni, $tel, $email, $dir, $dist, $ref]) {
            $this->clientes[$c] = DB::table('clientes')->insertGetId([
                'nombre' => $nombre, 'tipo' => $tipo, 'ruc' => $ruc, 'dni' => $dni, 'telefono' => $tel,
                'email' => $email, 'direccion' => $dir, 'distrito' => $dist, 'referencia' => $ref, 'estado' => 'activo',
            ] + $ts);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // SIMULACIÓN DE LAS ÚLTIMAS 2 SEMANAS
    // ═══════════════════════════════════════════════════════════════════
    private function simular(): void
    {
        for ($d = 0; $d < self::DIAS; $d++) {
            $dia = $this->ahora->copy()->startOfDay()->subDays(self::DIAS - 1 - $d);
            $h = fn (int $hh, int $mm = 0) => $dia->copy()->setTime($hh, $mm);

            // Todos los eventos del día se ordenan por hora y se ejecutan en ese orden,
            // así el kardex muestra cada movimiento a la hora en que de verdad ocurrió.
            $eventos = [
                [$h(4, 30), fn () => $this->produccionDelDia($dia, $d)],
                [$h(8, 0),  fn () => $this->convertirOrdenes($dia, $d)],
                [$h(11, 0), fn () => $this->recibirPendientes($h(11, 0))],
                [$h(17, 0), fn () => $this->mermas($dia, $d)],
                [$h(21, 0), fn () => $this->generarOrdenes($dia, $d)],
            ];
            foreach ($this->planVentas($dia) as $venta) {
                $eventos[] = [$venta[0], fn () => $this->registrarVenta(...$venta)];
            }
            if ($d === 3 || $d === self::DIAS - 2) {
                $eventos[] = [$h(15, 0), fn () => $this->compraManual($dia, $d)];
                $eventos[] = [$h(16, 30), fn () => $this->recibirPendientes($h(16, 30))];
            }
            if ($d === self::DIAS - 8) {
                // Conteo físico de los 10 insumos fijos: 2 no coinciden
                $eventos[] = [$h(19, 30), fn () => $this->conteoFisico($h(19, 30), ['huevos' => -4, 'azucar' => -0.3])];
            }
            if ($d === self::DIAS - 2) {
                // Segundo conteo (ayer): solo 1 no coincide
                $eventos[] = [$h(19, 30), fn () => $this->conteoFisico($h(19, 30), ['harina' => -0.8])];
                // Ayer se malogró mantequilla y se rompió un frasco de esencia: ambos quedan en
                // su mínimo y sus órdenes quedan pendientes (con proveedor / sin proveedor)
                $eventos[] = [$h(20, 40), fn () => $this->mermaEspecial($h(20, 40), 'mantequilla', 'Mantequilla en mal estado (se cortó la cadena de frío)')];
                $eventos[] = [$h(20, 45), fn () => $this->mermaEspecial($h(20, 45), 'esencia', 'Frasco de esencia de vainilla roto')];
            }

            usort($eventos, fn ($a, $b) => $a[0] <=> $b[0]);   // orden estable (PHP 8)
            foreach ($eventos as [, $evento]) $evento();
        }
    }

    /** 04:30 — Se produce cada producto hasta su "objetivo" del día (como en una panadería real). */
    private function produccionDelDia(Carbon $dia, int $d): void
    {
        $hora = $dia->copy()->setTime(4, 30);
        $usuario = $d % 2 === 0 ? 'cmamani' : 'almacenero';

        foreach ($this->recetas as $p => [$rend, $items]) {
            // La torta tres leches deja de hacerse los últimos 3 días: queda bajo su
            // mínimo para mostrar la alerta de stock bajo de un producto.
            if ($p === 'tres_leches' && $d >= self::DIAS - 3) continue;

            $falta = $this->pr[$p]['objetivo'] - $this->stockPr[$p];
            if ($falta <= 0) continue;
            $lotes = (int) ceil($falta / $rend);

            // Solo lo que alcanzan los insumos
            foreach ($items as $i => $cant) {
                $lotes = min($lotes, (int) floor(($this->stockMp[$i] + 1e-9) / $cant));
            }
            if ($lotes <= 0) continue;

            $t = $this->tick($hora);
            if (!$t) return;
            $cantidad = $lotes * $rend;
            $idProd = DB::table('producciones')->insertGetId([
                'id_producto' => $this->pr[$p]['id'], 'id_usuario' => $this->u[$usuario],
                'cantidad' => $cantidad, 'fecha' => $dia->toDateString(),
                'observacion' => 'Producción del turno mañana', 'created_at' => $t, 'updated_at' => $t,
            ]);

            foreach ($items as $i => $cant) {
                $this->movimientoMp($i, 'salida', 'produccion', -round($cant * $lotes, 3), $idProd,
                    "Producción #{$idProd} — {$this->pr[$p]['nombre']}", $usuario, $t);
            }
            $this->movimientoPr($p, 'entrada', 'produccion', $cantidad, $idProd, "Producción #{$idProd}", $usuario, $t);
        }
    }

    /**
     * 21:00 — Revisión de stock bajo (lo mismo que hace el botón "Generar órdenes" y el
     * comando diario): una orden automática por cada insumo en su mínimo, sin duplicar
     * las pendientes. Las de ayer y las de hoy quedan PENDIENTES (el almacenero aún no
     * las convierte); las anteriores se convierten en compra a la mañana siguiente.
     */
    private function generarOrdenes(Carbon $dia, int $d): void
    {
        foreach ($this->mp as $c => $m) {
            if ($this->stockMp[$c] > $m['minimo'] || isset($this->ordenPendiente[$c]) || isset($this->porConvertir[$c])) continue;

            $t = $this->tick($dia->copy()->setTime(21, 0));
            if (!$t) return;
            $idOrden = DB::table('ordenes_automaticas')->insertGetId([
                'id_materia' => $m['id'], 'id_proveedor' => $m['id_prov'],
                'stock_al_generar' => $this->stockMp[$c], 'stock_minimo' => $m['minimo'],
                // Igual que el comando: cantidad de reposición, o el doble del mínimo si no tiene
                'cantidad_sugerida' => $m['prov'] ? $m['repo'] : $m['minimo'] * 2, 'id_compra' => null,
                'estado' => 'pendiente', 'created_at' => $t, 'updated_at' => $t,
            ]);

            if (!$m['id_prov'] || $d >= self::DIAS - 2) {
                $this->ordenPendiente[$c] = $idOrden;
            } else {
                $this->porConvertir[$c] = $idOrden;
            }
        }
    }

    /**
     * 08:00 — El almacenero convierte en compra las órdenes de anoche (igual que
     * OrdenesAutomaticasController@convertir) y a las 11:00 llega el pedido.
     * La primera orden de todas se DESCARTA (ejemplo): esa noche se vuelve a generar.
     */
    private function convertirOrdenes(Carbon $dia, int $d): void
    {
        $ordenes = $this->porConvertir;
        $this->porConvertir = [];

        foreach ($ordenes as $c => $idOrden) {
            $m = $this->mp[$c];
            $tCompra = $this->tick($dia->copy()->setTime(8, 0));
            if (!$tCompra) return;

            if (!$this->hayDescartada) {
                $this->hayDescartada = true;
                DB::table('ordenes_automaticas')->where('id', $idOrden)->update(['estado' => 'descartada', 'updated_at' => $tCompra]);
                continue;
            }

            $usuario = $d % 2 === 0 ? 'almacenero' : 'cmamani';
            $precio  = $this->costoMp[$c];
            $cant    = $m['repo'];
            $sub     = round($cant * $precio, 2);
            $igv     = round($sub * 0.18, 2);
            $idCompra = DB::table('compras')->insertGetId([
                'id_proveedor' => $m['id_prov'], 'id_usuario' => $this->u[$usuario], 'numero_doc' => null,
                'fecha_compra' => $dia->toDateString(), 'subtotal' => $sub, 'igv' => $igv, 'total' => $sub + $igv,
                'estado' => 'pendiente',
                'observaciones' => "Generada automáticamente por stock bajo (Orden Automática #{$idOrden})",
                'created_at' => $tCompra, 'updated_at' => $tCompra,
            ]);
            DB::table('compra_detalles')->insert([
                'id_compra' => $idCompra, 'id_materia' => $m['id'], 'cantidad' => $cant,
                'precio_unitario' => $precio, 'subtotal' => $sub, 'created_at' => $tCompra, 'updated_at' => $tCompra,
            ]);
            DB::table('ordenes_automaticas')->where('id', $idOrden)
                ->update(['estado' => 'convertida', 'id_compra' => $idCompra, 'updated_at' => $tCompra]);

            $this->porRecibir[] = [$idCompra, [[$c, $cant, $precio]], $usuario];   // llega a las 11:00
        }
    }

    /** Recepción de las compras que están por llegar (11:00 y 16:30). */
    private function recibirPendientes(Carbon $hora): void
    {
        $compras = $this->porRecibir;
        $this->porRecibir = [];
        foreach ($compras as [$idCompra, $items, $usuario]) {
            $this->recibirCompra($idCompra, $items, $hora, $usuario);
        }
    }

    /**
     * Dos compras con factura hechas a mano (sin orden automática), con precio del día.
     * La de ayer todavía no llega: queda PENDIENTE (ejemplo para el botón "Recibir").
     */
    private function compraManual(Carbon $dia, int $d): void
    {
        $items = [['harina', 50, round($this->costoMp['harina'] * (1 + mt_rand(-4, 4) / 100), 2)],
                  ['azucar', 25, round($this->costoMp['azucar'] * (1 + mt_rand(-4, 4) / 100), 2)]];
        $t = $this->tick($dia->copy()->setTime(15, 0));
        if (!$t) return;

        $sub = 0;
        foreach ($items as [$c, $cant, $precio]) $sub += round($cant * $precio, 2);
        $igv = round($sub * 0.18, 2);
        $numero = sprintf('F001-%06d', ++$this->docProveedor['molino']);
        $idCompra = DB::table('compras')->insertGetId([
            'id_proveedor' => $this->mp['harina']['id_prov'], 'id_usuario' => $this->u['cmamani'], 'numero_doc' => $numero,
            'fecha_compra' => $dia->toDateString(), 'subtotal' => $sub, 'igv' => $igv, 'total' => $sub + $igv,
            'estado' => 'pendiente', 'observaciones' => 'Compra semanal de harina y azúcar',
            'created_at' => $t, 'updated_at' => $t,
        ]);
        foreach ($items as [$c, $cant, $precio]) {
            DB::table('compra_detalles')->insert([
                'id_compra' => $idCompra, 'id_materia' => $this->mp[$c]['id'], 'cantidad' => $cant,
                'precio_unitario' => $precio, 'subtotal' => round($cant * $precio, 2), 'created_at' => $t, 'updated_at' => $t,
            ]);
        }
        if ($d === 3) {
            $this->porRecibir[] = [$idCompra, $items, 'cmamani'];   // llega a las 16:30
        }
    }

    /** Igual que ComprasController@recibir: entra al stock y actualiza el último precio. */
    private function recibirCompra(int $idCompra, array $items, Carbon $hora, string $usuario): void
    {
        $t = $this->tick($hora);
        if (!$t) return;
        foreach ($items as [$c, $cant, $precio]) {
            $this->movimientoMp($c, 'entrada', 'compra', $cant, $idCompra, "Compra #{$idCompra}", $usuario, $t);
            $this->costoMp[$c] = $precio;
        }
        DB::table('compras')->where('id', $idCompra)->update(['estado' => 'recibida', 'updated_at' => $t]);
    }

    /** Pedidos fijos de clientes + ventas de mostrador del día: [hora, cliente, items, pago, observación]. */
    private function planVentas(Carbon $dia): array
    {
        $ventas = [];
        $dow = $dia->dayOfWeekIso; // 1 = lunes … 7 = domingo

        // Pedidos fijos
        $ventas[] = [$dia->copy()->setTime(6, 45), 'bodega', ['frances' => 80, 'ciabatta' => 25], 'efectivo', 'Pedido diario de la bodega'];
        if (in_array($dow, [1, 3, 5], true)) {
            $ventas[] = [$dia->copy()->setTime(7, 0), 'colegio', ['frances' => 150, 'yema' => 60], 'yape', 'Desayuno escolar'];
        }
        if (in_array($dow, [2, 4, 6], true)) {
            $ventas[] = [$dia->copy()->setTime(9, 30), 'resto', ['ciabatta' => 40, 'molde' => 2], 'tarjeta', 'Pedido del restaurante'];
        }

        // Mostrador: probabilidad de que el cliente lleve el producto y cuántas unidades
        $carta = [
            'frances' => [45, 4, 20], 'ciabatta' => [18, 4, 12], 'yema' => [18, 4, 15], 'chancay' => [12, 2, 8],
            'integral' => [8, 3, 10], 'molde' => [8, 1, 2], 'emp_carne' => [12, 1, 4], 'emp_pollo' => [12, 1, 4],
            'alfajor' => [10, 2, 6], 'galleta' => [6, 3, 8], 'keke' => [5, 1, 1], 'torta_choc' => [3, 1, 1], 'tres_leches' => [3, 1, 1],
        ];
        $pagos = ['efectivo', 'efectivo', 'efectivo', 'yape', 'yape', 'plin', 'tarjeta'];
        $nMostrador = 16 + mt_rand(0, 6) + ($dow >= 6 ? 6 : 0);
        for ($i = 0; $i < $nMostrador; $i++) {
            $items = [];
            foreach ($carta as $p => [$prob, $min, $max]) {
                if (mt_rand(1, 100) <= $prob) $items[$p] = mt_rand($min, $max);
            }
            if (!$items) $items['frances'] = mt_rand(5, 15);
            $cliente = mt_rand(1, 10) === 1 ? (mt_rand(0, 1) ? 'juan' : 'rosa') : 'general';
            $hora = $dia->copy()->setTime(6, 30)->addMinutes(mt_rand(0, 14 * 60));
            $ventas[] = [$hora, $cliente, $items, $pagos[array_rand($pagos)], null];
        }

        return $ventas;
    }

    /** Igual que VentasController@store: solo se vende lo que hay en stock. */
    private function registrarVenta(Carbon $hora, string $cliente, array $items, string $pago, ?string $obs): void
    {
        {
            $detalles = [];
            foreach ($items as $p => $cant) {
                $cant = min($cant, $this->stockPr[$p]);
                if ($cant > 0) $detalles[$p] = $cant;
            }
            if (!$detalles) return;

            $t = $this->tick($hora);
            if (!$t) return;   // sería en el futuro

            $usuario  = mt_rand(1, 5) === 1 ? 'admin' : 'mquispe';
            $subtotal = 0;
            foreach ($detalles as $p => $cant) $subtotal += $this->pr[$p]['precio'] * $cant;
            $subtotal = round($subtotal, 2);
            $igv = round($subtotal * 0.18, 2);

            $idVenta = DB::table('ventas')->insertGetId([
                'id_usuario' => $this->u[$usuario], 'id_cliente' => $this->clientes[$cliente],
                'numero_venta' => 'TMP-' . uniqid(), 'fecha_venta' => $t,
                'subtotal' => $subtotal, 'igv' => $igv, 'total' => $subtotal + $igv,
                'tipo_pago' => $pago, 'estado' => 'completada', 'observaciones' => $obs,
                'created_at' => $t, 'updated_at' => $t,
            ]);
            $numero = 'V-' . str_pad($idVenta, 6, '0', STR_PAD_LEFT);
            DB::table('ventas')->where('id', $idVenta)->update(['numero_venta' => $numero]);

            foreach ($detalles as $p => $cant) {
                DB::table('venta_detalles')->insert([
                    'id_venta' => $idVenta, 'id_producto' => $this->pr[$p]['id'], 'cantidad' => $cant,
                    'precio_unitario' => $this->pr[$p]['precio'], 'subtotal' => round($this->pr[$p]['precio'] * $cant, 2),
                    'created_at' => $t, 'updated_at' => $t,
                ]);
                $this->movimientoPr($p, 'salida', 'venta', -$cant, $idVenta, "Venta {$numero}", $usuario, $t);
            }
        }
    }

    /** 17:00 — Mermas ocasionales de insumos. */
    private function mermas(Carbon $dia, int $d): void
    {
        $plan = [
            2  => ['huevos', 12, 'Huevos rotos al descargar el pedido'],
            5  => ['leche', 2, 'Leche fresca vencida'],
            8  => ['naranjas', 1, 'Naranjas en mal estado'],
            10 => ['cebolla', 0.5, 'Cebolla malograda por humedad'],
            11 => ['huevos', 6, 'Huevos rotos en el almacén'],
        ];
        if (!isset($plan[$d])) return;
        [$c, $cant, $motivo] = $plan[$d];
        if ($this->stockMp[$c] < $cant) return;

        $t = $this->tick($dia->copy()->setTime(17, 0));
        if (!$t) return;
        $usuario = $d % 2 === 0 ? 'almacenero' : 'cmamani';
        $this->movimientoMp($c, 'salida', 'merma', -$cant, null, $motivo, $usuario, $t);
    }

    /** Merma que deja el insumo exactamente en su mínimo (si estaba por encima). */
    private function mermaEspecial(Carbon $hora, string $c, string $motivo): void
    {
        $exceso = round($this->stockMp[$c] - $this->mp[$c]['minimo'], 3);
        if ($exceso <= 0) return;
        $t = $this->tick($hora);
        if (!$t) return;
        $this->movimientoMp($c, 'salida', 'merma', -$exceso, null, $motivo, 'almacenero', $t);
    }

    /** Igual que ConteoFisicoController@store, sobre los 10 insumos fijos. */
    private function conteoFisico(Carbon $hora, array $diferencias): void
    {
        $t = $this->tick($hora);
        if (!$t) return;
        $lote = $t->format('YmdHis');

        foreach (self::INSUMOS_CONTEO as $c) {
            $sistema = $this->stockMp[$c];
            $fisico  = max(0, round($sistema + ($diferencias[$c] ?? 0), 3));
            $dif     = round($fisico - $sistema, 3);

            DB::table('conteos_fisicos')->insert([
                'lote' => $lote, 'id_materia' => $this->mp[$c]['id'], 'id_usuario' => $this->u['almacenero'],
                'stock_sistema' => $sistema, 'stock_fisico' => $fisico, 'diferencia' => $dif,
                'observacion' => 'Conteo de los 10 insumos de control', 'created_at' => $t, 'updated_at' => $t,
            ]);

            if ($dif != 0) {
                $this->movimientoMp($c, 'ajuste', 'ajuste_manual', $dif, null,
                    "Conteo físico #{$lote} (" . ($dif > 0 ? '+' : '') . number_format($dif, 3) . ')', 'almacenero', $t);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // REGISTRO DE MOVIMIENTOS (mueven el stock paso a paso, así todo cuadra)
    // ═══════════════════════════════════════════════════════════════════

    /** $delta: positivo entra, negativo sale. */
    private function movimientoMp(string $c, string $tipo, string $motivo, float $delta, ?int $ref, string $obs, string $usuario, Carbon $t): void
    {
        $antes   = $this->stockMp[$c];
        $despues = round($antes + $delta, 3);
        $this->stockMp[$c] = $despues;
        $this->movimientos[] = [
            'id_materia' => $this->mp[$c]['id'], 'id_usuario' => $this->u[$usuario],
            'tipo' => $tipo, 'motivo' => $motivo, 'referencia_id' => $ref,
            'cantidad' => abs(round($delta, 3)), 'stock_antes' => $antes, 'stock_despues' => $despues,
            'observacion' => $obs, 'created_at' => $t->copy(),
        ];
    }

    private function movimientoPr(string $p, string $tipo, string $motivo, int $delta, ?int $ref, string $obs, string $usuario, Carbon $t): void
    {
        $antes   = $this->stockPr[$p];
        $despues = $antes + $delta;
        $this->stockPr[$p] = $despues;
        $this->kardex[] = [
            'id_producto' => $this->pr[$p]['id'], 'id_usuario' => $this->u[$usuario],
            'tipo' => $tipo, 'motivo' => $motivo, 'referencia_id' => $ref,
            'cantidad' => abs($delta), 'stock_antes' => $antes, 'stock_despues' => $despues,
            'observacion' => $obs, 'created_at' => $t->copy(),
        ];
    }

    /**
     * Devuelve un instante estrictamente posterior al último registrado (así el kardex,
     * que ordena por fecha, ve los movimientos en el orden real). null si cae en el futuro.
     */
    private function tick(Carbon $hora): ?Carbon
    {
        $t = $hora->greaterThan($this->cursor) ? $hora->copy() : $this->cursor->copy()->addSecond();
        if ($t->greaterThan($this->ahora)) return null;
        $this->cursor = $t;
        return $t->copy();
    }

    /** Guarda los movimientos y deja cada stock igual al último saldo del kardex. */
    private function guardarSaldos(): void
    {
        foreach (array_chunk($this->movimientos, 500) as $lote) DB::table('movimientos_inventario')->insert($lote);
        foreach (array_chunk($this->kardex, 500) as $lote)      DB::table('kardex_productos')->insert($lote);

        foreach ($this->mp as $c => $m) {
            DB::table('materia_prima')->where('id', $m['id'])
                ->update(['stock_actual' => $this->stockMp[$c], 'costo_unitario' => $this->costoMp[$c]]);
        }
        foreach ($this->pr as $p => $m) {
            DB::table('productos')->where('id', $m['id'])->update(['stock_actual' => $this->stockPr[$p]]);
        }
    }

    /** Redondea a cantidades "de almacén": unidades enteras, 50 mL, 0.1 / 0.5 / 1 kg. */
    private function redondear(float $x, string $unidad): float
    {
        return match (true) {
            $unidad === 'und' => max(1, ceil($x)),
            $unidad === 'mL'  => max(50, ceil($x / 50) * 50),
            $x < 1            => max(0.1, ceil($x * 10) / 10),
            $x < 10           => ceil($x * 2) / 2,
            default           => ceil($x),
        };
    }
}
