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

// Redirigir raíz al dashboard
Route::get('/', fn() => redirect()->route('dashboard'));

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Catálogo
    Route::resource('categorias',   CategoriasController::class);
    Route::resource('productos',    ProductosController::class);

    // Inventario
    Route::resource('materia-prima', MateriaPrimaController::class);
    Route::get('movimientos',       [MovimientosController::class, 'index'])->name('movimientos.index');

    // Proveedores y Compras
    Route::resource('proveedores',  ProveedoresController::class);
    Route::resource('compras',      ComprasController::class)->only(['index','create','store','show']);
    Route::put('compras/{compra}/recibir', [ComprasController::class, 'recibir'])->name('compras.recibir');

    // Ventas
    Route::resource('ventas', VentasController::class)->only(['index','create','store','show']);
    Route::put('ventas/{venta}/anular', [VentasController::class, 'anular'])->name('ventas.anular');

    // Clientes y distribución
    Route::resource('clientes', ClientesController::class);

    // Usuarios (solo admin)
    Route::resource('usuarios', UsuariosController::class);
    
    // Producción
    Route::get('produccion',                  [ProduccionController::class, 'index'])->name('produccion.index');
    Route::get('produccion/crear',            [ProduccionController::class, 'create'])->name('produccion.create');
    Route::post('produccion',                 [ProduccionController::class, 'store'])->name('produccion.store');
    Route::get('produccion/{produccion}',     [ProduccionController::class, 'show'])->name('produccion.show');
    Route::get('produccion/recetas/listado',  [ProduccionController::class, 'recetas'])->name('produccion.recetas');
    Route::post('produccion/recetas/guardar', [ProduccionController::class, 'crearReceta'])->name('produccion.guardar-receta');
    Route::get('produccion/ingredientes/{producto}', [ProduccionController::class, 'ingredientes'])->name('produccion.ingredientes');
});

// Auth routes (login/logout de Laravel Breeze o similar)
require __DIR__.'/auth.php';
