<?php

namespace Tests\Feature;

use App\Models\ActividadUsuario;
use App\Models\Categoria;
use App\Models\PermisoUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Historial de actividad y estado de actividad de los usuarios.
 */
class ActividadUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_iniciar_sesion_queda_en_el_historial(): void
    {
        $u = Usuario::factory()->create(['usuario' => 'pepe']);

        $this->post('/login', ['usuario' => 'pepe', 'password' => 'password'])->assertRedirect();

        $this->assertDatabaseHas('actividad_usuarios', ['id_usuario' => $u->id, 'accion' => 'inicio_sesion']);
    }

    public function test_un_ingreso_fallido_queda_registrado(): void
    {
        $u = Usuario::factory()->create(['usuario' => 'pepe']);

        $this->post('/login', ['usuario' => 'pepe', 'password' => 'mala']);
        $this->post('/login', ['usuario' => 'nadie', 'password' => 'x']);

        $this->assertDatabaseHas('actividad_usuarios', ['id_usuario' => $u->id, 'accion' => 'login_fallido']);
        $this->assertDatabaseHas('actividad_usuarios', ['id_usuario' => null, 'usuario_texto' => 'nadie', 'accion' => 'login_fallido']);
    }

    public function test_crear_una_categoria_queda_registrado_con_una_frase_legible(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->post(route('categorias.store'), ['nombre' => 'Bebidas', 'estado' => 'activo'])
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('actividad_usuarios', [
            'id_usuario' => $admin->id, 'accion' => 'crear', 'modulo' => 'Catálogo', 'descripcion' => 'Creó la categoría "Bebidas"',
        ]);
    }

    public function test_si_falla_la_validacion_no_se_registra_nada(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->post(route('categorias.store'), ['nombre' => '', 'estado' => 'activo'])
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseMissing('actividad_usuarios', ['accion' => 'crear']);
    }

    public function test_solo_el_admin_ve_el_historial(): void
    {
        $u = Usuario::factory()->create();
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'ventas']);
        $admin = Usuario::factory()->admin()->create();
        ActividadUsuario::create(['id_usuario' => $u->id, 'accion' => 'crear', 'modulo' => 'Ventas', 'descripcion' => 'Registró la venta V-000001 por S/ 10.00', 'created_at' => now()]);

        $this->actingAs($u->fresh())->get(route('actividad.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('actividad.index'))->assertOk()->assertSee('Registró la venta V-000001');
        $this->actingAs($admin)->get(route('actividad.index', ['usuario' => $u->id]))->assertOk()->assertSee($u->nombre);
        $this->actingAs($admin)->get(route('actividad.index', ['accion' => 'login_fallido']))->assertOk()->assertDontSee('V-000001');
    }

    public function test_usuarios_muestra_el_estado_de_actividad_y_filtra_los_inactivos(): void
    {
        $admin  = Usuario::factory()->admin()->create();
        $viejo  = Usuario::factory()->create(['nombre' => 'Usuario Olvidado', 'ultimo_acceso' => now()->subDays(40)]);
        $activo = Usuario::factory()->create(['nombre' => 'Usuario Diario', 'ultimo_acceso' => now()->subDay()]);
        ActividadUsuario::create(['id_usuario' => $activo->id, 'accion' => 'crear', 'modulo' => 'Ventas', 'descripcion' => 'Registró la venta V-000777', 'created_at' => now()->subDay()]);
        // Un cierre de sesión posterior NO debe ocultar la última acción real
        ActividadUsuario::create(['id_usuario' => $activo->id, 'accion' => 'cierre_sesion', 'modulo' => 'Sesión', 'descripcion' => 'Cerró sesión', 'created_at' => now()->subDay()->addHour()]);

        $this->assertSame('sin_actividad', $viejo->estadoActividad()['clave']);
        $this->assertSame('activo', $activo->estadoActividad()['clave']);

        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk()
            ->assertSee('Sin actividad hace 40 días')
            ->assertSee('Registró la venta V-000777');

        $this->actingAs($admin)->get(route('usuarios.index', ['actividad' => 'sin_actividad']))->assertOk()
            ->assertSee('Usuario Olvidado')->assertDontSee('Usuario Diario');
    }
}
