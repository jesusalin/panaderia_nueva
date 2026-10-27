<?php

namespace Tests\Feature;

use App\Http\Controllers\MenuController;
use App\Models\Configuracion;
use App\Models\PermisoUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Ordenar menú": solo el admin reordena el menú lateral (arrastrar y soltar)
 * y ese orden se aplica a todos los usuarios.
 */
class OrdenMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // no depende de haber corrido "npm run build"
    }

    private function ordenEjemplo(): array
    {
        return [
            'secciones' => ['sec-ventas', 'dashboard', 'sec-inventario'],
            'items'     => [
                'sec-inventario' => ['conteo-fisico', 'materia-prima'],
                'sec-ventas'     => ['clientes', 'ventas'],
            ],
        ];
    }

    private function ordenGuardado(): ?array
    {
        return Configuracion::obtener(MenuController::CLAVE);
    }

    public function test_un_invitado_no_puede_guardar_el_orden(): void
    {
        $this->postJson(route('menu.orden.guardar'), $this->ordenEjemplo())
             ->assertUnauthorized();
    }

    public function test_un_usuario_que_no_es_admin_no_puede_guardar_ni_restablecer(): void
    {
        $usuario = Usuario::factory()->create();
        Configuracion::guardar(MenuController::CLAVE, $this->ordenEjemplo());

        $this->actingAs($usuario)
             ->postJson(route('menu.orden.guardar'), ['secciones' => ['dashboard'], 'items' => []])
             ->assertForbidden();
        $this->actingAs($usuario)
             ->deleteJson(route('menu.orden.restablecer'))
             ->assertForbidden();

        $this->assertSame($this->ordenEjemplo(), $this->ordenGuardado());
    }

    public function test_el_admin_guarda_el_orden_general(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
             ->postJson(route('menu.orden.guardar'), $this->ordenEjemplo())
             ->assertOk()
             ->assertJson(['ok' => true]);

        $this->assertSame($this->ordenEjemplo(), $this->ordenGuardado());
    }

    public function test_rechaza_identificadores_con_caracteres_raros(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
             ->postJson(route('menu.orden.guardar'), [
                 'secciones' => ['<script>alert(1)</script>'],
                 'items'     => [],
             ])
             ->assertStatus(422);

        $this->assertNull($this->ordenGuardado());
    }

    public function test_restablecer_vuelve_al_orden_original(): void
    {
        $admin = Usuario::factory()->admin()->create();
        Configuracion::guardar(MenuController::CLAVE, $this->ordenEjemplo());

        $this->actingAs($admin)
             ->deleteJson(route('menu.orden.restablecer'))
             ->assertOk();

        $this->assertNull($this->ordenGuardado());
    }

    public function test_el_admin_ve_el_boton_ordenar_menu(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
             ->get(route('dashboard'))
             ->assertOk()
             ->assertSee('id="menuOrdenEditar"', false)
             ->assertSee('Sortable.min.js', false);
    }

    public function test_un_usuario_normal_no_ve_el_boton_pero_recibe_el_orden_del_admin(): void
    {
        Configuracion::guardar(MenuController::CLAVE, $this->ordenEjemplo());
        $usuario = Usuario::factory()->create();
        PermisoUsuario::create(['id_usuario' => $usuario->id, 'modulo' => 'ventas']);

        $this->actingAs($usuario)
             ->get(route('dashboard'))
             ->assertOk()
             ->assertDontSee('id="menuOrdenEditar"', false)
             ->assertDontSee('Sortable.min.js', false)
             ->assertSee('"sec-ventas","dashboard","sec-inventario"', false)
             ->assertSee('data-menu-id="sec-ventas"', false);
    }
}
