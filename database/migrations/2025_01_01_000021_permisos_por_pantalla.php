<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los permisos pasan de "módulo completo" a "pantalla por pantalla".
 * Quien tenía un módulo (p. ej. "inventario") pasa a tener TODAS sus pantallas,
 * así nadie pierde acceso; luego el admin quita lo que no corresponda.
 * Ventas y Clientes no tienen pantallas: se quedan igual.
 */
return new class extends Migration
{
    // Copia fija del catálogo (no se usa Usuario::PERMISOS para que la migración
    // no cambie de comportamiento si en el futuro se agregan pantallas).
    private const PANTALLAS = [
        'inventario' => ['inventario.materia_prima', 'inventario.movimientos', 'inventario.conteo', 'inventario.kardex'],
        'compras'    => ['compras.proveedores', 'compras.compras', 'compras.ordenes'],
        'catalogo'   => ['catalogo.productos', 'catalogo.categorias'],
        'produccion' => ['produccion.produccion', 'produccion.recetas'],
        'reportes'   => ['reportes.resumen', 'reportes.stock', 'reportes.kardex'],
    ];

    public function up(): void
    {
        foreach (self::PANTALLAS as $modulo => $pantallas) {
            $filas = DB::table('permisos_usuario')->where('modulo', $modulo)->get();
            foreach ($filas as $fila) {
                foreach ($pantallas as $pantalla) {
                    DB::table('permisos_usuario')->insertOrIgnore([
                        'id_usuario' => $fila->id_usuario,
                        'modulo'     => $pantalla,
                        'created_at' => now(),
                    ]);
                }
            }
            DB::table('permisos_usuario')->where('modulo', $modulo)->delete();
        }
    }

    public function down(): void
    {
        // Vuelve al formato por módulo: quien tenga al menos una pantalla recibe el módulo.
        foreach (self::PANTALLAS as $modulo => $pantallas) {
            $usuarios = DB::table('permisos_usuario')->whereIn('modulo', $pantallas)
                ->distinct()->pluck('id_usuario');
            foreach ($usuarios as $idUsuario) {
                DB::table('permisos_usuario')->insertOrIgnore([
                    'id_usuario' => $idUsuario,
                    'modulo'     => $modulo,
                    'created_at' => now(),
                ]);
            }
            DB::table('permisos_usuario')->whereIn('modulo', $pantallas)->delete();
        }
    }
};
