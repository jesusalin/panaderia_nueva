<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\MateriaPrimaController;
use App\Http\Controllers\ConteoFisicoController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\MovimientosController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\OrdenesAutomaticasController;
use App\Http\Controllers\TiemposOperacionController;

// Redirigir raíz al dashboard
Route::get('/', fn() => redirect()->route('dashboard'));

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {

    // Dashboard: visible para cualquier usuario autenticado, sin importar sus permisos
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // El cronómetro de tiempos de operación corre en pantallas de distintos
    // módulos por igual, así que el registro queda abierto a cualquier usuario.
    Route::post('tiempos-operacion/registrar', [TiemposOperacionController::class, 'store'])->name('tiempos-operacion.store');

    // ── Reservado solo para ADMIN (gestión de usuarios y accesos) ──
    Route::middleware(['modulo:usuarios'])->group(function () {
        Route::resource('usuarios', UsuariosController::class);
        Route::put('usuarios/{usuario}/toggle-estado', [UsuariosController::class, 'toggleEstado'])->name('usuarios.toggle-estado');
    });

    // ── Módulo: Catálogo ─────────────────────────────────────────
    Route::middleware(['modulo:catalogo'])->group(function () {
        Route::resource('categorias', CategoriasController::class);
        Route::put('categorias/{categoria}/toggle-estado', [CategoriasController::class, 'toggleEstado'])->name('categorias.toggle-estado');
        Route::resource('productos',  ProductosController::class);
        Route::put('productos/{producto}/toggle-estado', [ProductosController::class, 'toggleEstado'])->name('productos.toggle-estado');
    });

    // ── Módulo: Inventario ───────────────────────────────────────
    Route::middleware(['modulo:inventario'])->group(function () {
        Route::resource('materia-prima', MateriaPrimaController::class);
        Route::put('materia-prima/{materiaPrima}/toggle-estado', [MateriaPrimaController::class, 'toggleEstado'])->name('materia-prima.toggle-estado');
        Route::get('materia-prima/{materiaPrima}/ajuste',  [MateriaPrimaController::class, 'ajusteForm'])->name('materia-prima.ajuste');
        Route::post('materia-prima/{materiaPrima}/ajuste', [MateriaPrimaController::class, 'ajusteStore'])->name('materia-prima.ajuste.store');
        Route::get('movimientos_materia_prima', [MovimientosController::class, 'index'])->name('movimientos.index');
        Route::get('movimientos_productos', [KardexController::class, 'index'])->name('kardex.index');
        Route::get('movimientos_productos/rotacion', [KardexController::class, 'rotacion'])->name('kardex.rotacion');

        Route::get('conteo-fisico',            [ConteoFisicoController::class, 'index'])->name('conteo-fisico.index');
        Route::post('conteo-fisico',           [ConteoFisicoController::class, 'store'])->name('conteo-fisico.store');
        Route::get('conteo-fisico/historial',  [ConteoFisicoController::class, 'historial'])->name('conteo-fisico.historial');
        Route::get('conteo-fisico/{lote}',     [ConteoFisicoController::class, 'detalleLote'])->name('conteo-fisico.detalle');
    });

    // ── Módulo: Producción ───────────────────────────────────────
    Route::middleware(['modulo:produccion'])->group(function () {
        Route::get('produccion',                  [ProduccionController::class, 'index'])->name('produccion.index');
        Route::get('produccion/crear',            [ProduccionController::class, 'create'])->name('produccion.create');
        Route::post('produccion',                 [ProduccionController::class, 'store'])->name('produccion.store');
        Route::get('produccion/{produccion}',     [ProduccionController::class, 'show'])->name('produccion.show');
        Route::delete('produccion/{produccion}',  [ProduccionController::class, 'destroy'])->name('produccion.destroy');
        Route::get('produccion/recetas/listado',  [ProduccionController::class, 'recetas'])->name('produccion.recetas');
        Route::post('produccion/recetas/guardar', [ProduccionController::class, 'crearReceta'])->name('produccion.guardar-receta');
        Route::get('produccion/ingredientes/{producto}', [ProduccionController::class, 'ingredientes'])->name('produccion.ingredientes');
    });

    // ── Módulo: Compras ──────────────────────────────────────────
    Route::middleware(['modulo:compras'])->group(function () {
        Route::put('proveedores/{proveedor}/toggle-estado', [ProveedoresController::class, 'toggleEstado'])->name('proveedores.toggle-estado');
        Route::resource('proveedores', ProveedoresController::class)
            ->parameters(['proveedores' => 'proveedor']);
        Route::resource('compras', ComprasController::class)->only(['index', 'create', 'store', 'show']);
        Route::put('compras/{compra}/recibir', [ComprasController::class, 'recibir'])->name('compras.recibir');
        Route::put('compras/{compra}/anular',  [ComprasController::class, 'anular'])->name('compras.anular');

        Route::get('ordenes-automaticas',  [OrdenesAutomaticasController::class, 'index'])->name('ordenes-automaticas.index');
        Route::post('ordenes-automaticas/generar', [OrdenesAutomaticasController::class, 'generar'])->name('ordenes-automaticas.generar');
        Route::post('ordenes-automaticas/{ordenAutomatica}/convertir', [OrdenesAutomaticasController::class, 'convertir'])->name('ordenes-automaticas.convertir');
        Route::post('ordenes-automaticas/{ordenAutomatica}/descartar', [OrdenesAutomaticasController::class, 'descartar'])->name('ordenes-automaticas.descartar');
    });

    // ── Módulo: Clientes ─────────────────────────────────────────
    Route::middleware(['modulo:clientes'])->group(function () {
        Route::resource('clientes', ClientesController::class);
    });

    // ── Módulo: Ventas ───────────────────────────────────────────
    Route::middleware(['modulo:ventas'])->group(function () {
        Route::get('ventas/exportar-excel', [VentasController::class, 'exportarExcel'])->name('ventas.exportar-excel');
        Route::resource('ventas', VentasController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::put('ventas/{venta}/anular', [VentasController::class, 'anular'])->name('ventas.anular');
        Route::get('ventas/{venta}/pdf', [VentasController::class, 'pdf'])->name('ventas.pdf');
    });

    // ── Módulo: Reportes ─────────────────────────────────────────
    Route::middleware(['modulo:reportes'])->group(function () {
        Route::get('tiempos-operacion', [TiemposOperacionController::class, 'index'])->name('tiempos-operacion.index');
        Route::post('tiempos-operacion/baseline', [TiemposOperacionController::class, 'actualizarBaseline'])->name('tiempos-operacion.baseline');
        Route::get('tiempos-operacion/exportar-pdf', [TiemposOperacionController::class, 'exportarPdf'])->name('tiempos-operacion.exportar-pdf');
    });
});

// Auth routes (login/logout de Laravel Breeze o similar)
require __DIR__.'/auth.php';
