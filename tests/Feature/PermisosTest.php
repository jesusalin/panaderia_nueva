<?php

namespace Tests\Feature;

use App\Models\PermisoUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El control de acceso por módulo (CheckModulo) es lo que separa a un
 * cajero de un administrador dentro del mismo sistema. Si esto falla,
 * cualquier usuario podría entrar a cualquier módulo.
 */
class PermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_sin_sesion_es_redirigido_al_login(): void
    {
        $response = $this->get(route('clientes.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_el_administrador_accede_a_cualquier_modulo_sin_permisos_asignados(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('clientes.index'));

        $response->assertOk();
    }

    public function test_un_usuario_normal_sin_el_permiso_del_modulo_recibe_403(): void
    {
        $usuario = Usuario::factory()->create(); // rol "usuario", sin permisos_usuario

        $response = $this->actingAs($usuario)->get(route('clientes.index'));

        $response->assertForbidden();
    }

    public function test_un_usuario_normal_con_el_permiso_del_modulo_si_puede_entrar(): void
    {
        $usuario = Usuario::factory()->create();
        PermisoUsuario::create(['id_usuario' => $usuario->id, 'modulo' => 'clientes']);

        $response = $this->actingAs($usuario)->get(route('clientes.index'));

        $response->assertOk();
    }

    public function test_tener_permiso_en_un_modulo_no_da_acceso_a_otro(): void
    {
        $usuario = Usuario::factory()->create();
        PermisoUsuario::create(['id_usuario' => $usuario->id, 'modulo' => 'clientes']);

        // Tiene "clientes" pero no "ventas": debe seguir bloqueado ahí.
        $response = $this->actingAs($usuario)->get(route('ventas.index'));

        $response->assertForbidden();
    }
}
