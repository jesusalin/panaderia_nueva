<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\MateriaPrimaController;
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

    // Dashboard: visible para cualquier usuario autenticado, sin importar su rol
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // El cronómetro de tiempos de operación corre en pantallas de vendedor
    // y almacenero por igual, así que el registro queda abierto a cualquier rol.
    Route::post('tiempos-operacion/registrar', [TiemposOperacionController::class, 'store'])->name('tiempos-operacion.store');

    // ── Solo ADMIN ──────────────────────────────────────────────
    Route::middleware(['role:admin'])->group(function () {
        // Catálogo (define qué se vende, es decisión administrativa)
        Route::resource('categorias', CategoriasController::class);
        Route::resource('productos',  ProductosController::class);

        // Usuarios y accesos al sistema
        Route::resource('usuarios', UsuariosController::class);
    });

    // ── ADMIN + ALMACENERO (gestiona inventario y compras) ──────
    Route::middleware(['role:admin,almacenero'])->group(function () {
        // Inventario
        Route::resource('materia-prima', MateriaPrimaController::class);
        Route::get('materia-prima/{materiaPrima}/ajuste',  [MateriaPrimaController::class, 'ajusteForm'])->name('materia-prima.ajuste');
        Route::post('materia-prima/{materiaPrima}/ajuste', [MateriaPrimaController::class, 'ajusteStore'])->name('materia-prima.ajuste.store');
        Route::get('movimientos_materia_prima', [MovimientosController::class, 'index'])->name('movimientos.index');

        // Proveedores y Compras
        Route::resource('proveedores', ProveedoresController::class)
            ->parameters(['proveedores' => 'proveedor']);
        Route::resource('compras', ComprasController::class)->only(['index', 'create', 'store', 'show']);
        Route::put('compras/{compra}/recibir', [ComprasController::class, 'recibir'])->name('compras.recibir');

        // Ordenes automaticas de reposicion
        Route::get('ordenes-automaticas',  [OrdenesAutomaticasController::class, 'index'])->name('ordenes-automaticas.index');
        Route::post('ordenes-automaticas/generar', [OrdenesAutomaticasController::class, 'generar'])->name('ordenes-automaticas.generar');
        Route::post('ordenes-automaticas/{ordenAutomatica}/convertir', [OrdenesAutomaticasController::class, 'convertir'])->name('ordenes-automaticas.convertir');
        Route::post('ordenes-automaticas/{ordenAutomatica}/descartar', [OrdenesAutomaticasController::class, 'descartar'])->name('ordenes-automaticas.descartar');

        // Kardex Productos
        Route::get('movimientos_productos', [KardexController::class, 'index'])->name('kardex.index');
        Route::get('movimientos_productos/rotacion', [KardexController::class, 'rotacion'])->name('kardex.rotacion');

        // Tiempos por operación: reporte (OE3 - reducción de tiempos muertos)
        Route::get('tiempos-operacion', [TiemposOperacionController::class, 'index'])->name('tiempos-operacion.index');
        Route::post('tiempos-operacion/baseline', [TiemposOperacionController::class, 'actualizarBaseline'])->name('tiempos-operacion.baseline');

        // Producción
        Route::get('produccion',                  [ProduccionController::class, 'index'])->name('produccion.index');
        Route::get('produccion/crear',            [ProduccionController::class, 'create'])->name('produccion.create');
        Route::post('produccion',                 [ProduccionController::class, 'store'])->name('produccion.store');
        Route::get('produccion/{produccion}',     [ProduccionController::class, 'show'])->name('produccion.show');
        Route::get('produccion/recetas/listado',  [ProduccionController::class, 'recetas'])->name('produccion.recetas');
        Route::post('produccion/recetas/guardar', [ProduccionController::class, 'crearReceta'])->name('produccion.guardar-receta');
        Route::get('produccion/ingredientes/{producto}', [ProduccionController::class, 'ingredientes'])->name('produccion.ingredientes');
    });

    // ── ADMIN + VENDEDOR (ventas y clientes) ─────────────────────
    Route::middleware(['role:admin,vendedor'])->group(function () {
        Route::resource('ventas', VentasController::class)->only(['index', 'create', 'store', 'show']);
        Route::put('ventas/{venta}/anular', [VentasController::class, 'anular'])->name('ventas.anular');

        Route::resource('clientes', ClientesController::class);
    });
});

// Auth routes (login/logout de Laravel Breeze o similar)
require __DIR__.'/auth.php';
