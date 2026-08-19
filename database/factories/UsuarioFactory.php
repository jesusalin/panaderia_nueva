<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nombre'   => fake()->name(),
            'usuario'  => fake()->unique()->userName(),
            'email'    => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'id_rol'   => Rol::firstOrCreate(
                ['nombre' => 'usuario'],
                ['descripcion' => 'Acceso según los módulos que le asigne el administrador']
            )->id,
            'estado'   => 'activo',
        ];
    }

    /**
     * Usuario con rol admin: acceso total a todos los módulos sin
     * necesidad de asignarle permisos_usuario uno por uno.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'id_rol' => Rol::firstOrCreate(
                ['nombre' => 'admin'],
                ['descripcion' => 'Acceso total al sistema']
            )->id,
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => 'inactivo']);
    }
}
