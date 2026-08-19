<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // RECETAS
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_producto')->unique()->constrained('productos');
            $table->integer('rendimiento')->default(1);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('receta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_receta')->constrained('recetas');
            $table->foreignId('id_materia')->constrained('materia_prima');
            $table->decimal('cantidad', 10, 3);
            $table->timestamps();
        });

        // PROVEEDORES
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('ruc', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('contacto', 100)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });

        // COMPRAS
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_proveedor')->constrained('proveedores');
            $table->foreignId('id_usuario')->constrained('usuarios');
            $table->string('numero_doc', 50)->nullable();
            $table->date('fecha_compra');
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('igv', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->enum('estado', ['pendiente', 'recibida', 'anulada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_compra')->constrained('compras');
            $table->foreignId('id_materia')->constrained('materia_prima');
            $table->decimal('cantidad', 10, 3);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        // CLIENTES Y VENTAS
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->default('Cliente General');
            $table->string('dni', 15)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuarios');
            $table->foreignId('id_cliente')->nullable()->constrained('clientes');
            $table->string('numero_venta', 20)->unique();
            $table->dateTime('fecha_venta');
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('igv', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->enum('tipo_pago', ['efectivo', 'yape', 'plin', 'tarjeta', 'otro'])->default('efectivo');
            $table->enum('estado', ['completada', 'anulada'])->default('completada');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_venta')->constrained('ventas');
            $table->foreignId('id_producto')->constrained('productos');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        // PRODUCCIONES Y MOVIMIENTOS
        Schema::create('producciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_producto')->constrained('productos');
            $table->foreignId('id_usuario')->constrained('usuarios');
            $table->integer('cantidad');
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_materia')->constrained('materia_prima');
            $table->foreignId('id_usuario')->constrained('usuarios');
            $table->enum('tipo', ['entrada', 'salida', 'ajuste']);
            $table->enum('motivo', ['compra', 'produccion', 'merma', 'ajuste_manual', 'devolucion']);
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->decimal('cantidad', 10, 3);
            $table->decimal('stock_antes', 10, 3);
            $table->decimal('stock_despues', 10, 3);
            $table->text('observacion')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('producciones');
        Schema::dropIfExists('venta_detalles');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('compra_detalles');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('receta_detalles');
        Schema::dropIfExists('recetas');
    }
};
