<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });

        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('abreviatura', 10);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_categoria')->constrained('categorias');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('costo_produccion', 10, 2)->default(0.00);
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->string('imagen', 300)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });

        Schema::create('materia_prima', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignId('id_unidad')->constrained('unidades_medida');
            $table->decimal('stock_actual', 10, 3)->default(0.000);
            $table->decimal('stock_minimo', 10, 3)->default(0.000);
            $table->decimal('costo_unitario', 10, 2)->default(0.00);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_prima');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('unidades_medida');
        Schema::dropIfExists('categorias');
    }
};
