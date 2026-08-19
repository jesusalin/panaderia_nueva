<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'id_categoria'      => Categoria::factory(),
            'nombre'            => fake()->unique()->words(2, true),
            'descripcion'       => fake()->sentence(),
            'precio_venta'      => fake()->randomFloat(2, 2, 50),
            'costo_produccion'  => fake()->randomFloat(2, 1, 20),
            'stock_actual'      => 50,
            'stock_minimo'      => 5,
            'estado'            => 'activo',
        ];
    }
}
