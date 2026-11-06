<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_iniciar_sesion_con_credenciales_correctas(): void
    {
        $usuario = Usuario::factory()->create(['password' => Hash::make('clave-correcta')]);

        $response = $this->post('/login', [
            'usuario'  => $usuario->usuario,
            'password' => 'clave-correcta',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_no_se_puede_iniciar_sesion_con_contrasena_incorrecta(): void
    {
        $usuario = Usuario::factory()->create(['password' => Hash::make('clave-correcta')]);

        $response = $this->post('/login', [
            'usuario'  => $usuario->usuario,
            'password' => 'clave-equivocada',
        ]);

        $response->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_un_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $usuario = Usuario::factory()->inactivo()->create(['password' => Hash::make('clave-correcta')]);

        $response = $this->post('/login', [
            'usuario'  => $usuario->usuario,
            'password' => 'clave-correcta',
        ]);

        $response->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_un_usuario_autenticado_puede_cerrar_sesion(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)->post('/logout');

        $this->assertGuest();
    }

    /**
     * Decisión del sistema: las cuentas las crea el administrador (no hay registro libre)
     * y las contraseñas olvidadas las renueva el admin (no hay correo de recuperación).
     * Por eso se quitaron las pruebas de esas pantallas que traía la plantilla Breeze.
     */
    public function test_no_hay_registro_libre_ni_recuperacion_por_correo(): void
    {
        foreach (['/register', '/forgot-password', '/reset-password/token', '/verify-email', '/confirm-password'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }
}
