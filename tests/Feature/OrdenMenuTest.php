<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Ordenar menú": cada usuario puede reordenar el menú lateral arrastrando y
 * soltando, y ese orden se guarda solo para él.
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

    public function test_un_invitado_no_puede_guardar_el_orden(): void
    {
        $this->postJson(route('menu.orden.guardar'), $this->ordenEjemplo())
             ->assertUnauthorized();
    }

    public function test_el_usuario_guarda_su_orden_y_queda_en_la_base_de_datos(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)
             ->postJson(route('menu.orden.guardar'), $this->ordenEjemplo())
             ->assertOk()
             ->assertJson(['ok' => true]);

        $this->assertSame($this->ordenEjemplo(), $usuario->fresh()->orden_menu);
    }

    public function test_el_orden_es_personal_y_no_afecta_a_otros_usuarios(): void
    {
        $uno  = Usuario::factory()->create();
        $otro = Usuario::factory()->create();

        $this->actingAs($uno)->postJson(route('menu.orden.guardar'), $this->ordenEjemplo())->assertOk();

        $this->assertNull($otro->fresh()->orden_menu);
    }

    public function test_rechaza_identificadores_con_caracteres_raros(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)
             ->postJson(route('menu.orden.guardar'), [
                 'secciones' => ['<script>alert(1)</script>'],
                 'items'     => [],
             ])
             ->assertStatus(422);

        $this->assertNull($usuario->fresh()->orden_menu);
    }

    public function test_restablecer_vuelve_al_orden_original(): void
    {
        $usuario = Usuario::factory()->create(['orden_menu' => $this->ordenEjemplo()]);

        $this->actingAs($usuario)
             ->deleteJson(route('menu.orden.restablecer'))
             ->assertOk();

        $this->assertNull($usuario->fresh()->orden_menu);
    }

    public function test_el_menu_muestra_el_boton_y_carga_el_orden_guardado(): void
    {
        $admin = Usuario::factory()->admin()->create(['orden_menu' => $this->ordenEjemplo()]);

        $this->actingAs($admin)
             ->get(route('dashboard'))
             ->assertOk()
             ->assertSee('Ordenar menú')
             ->assertSee('data-menu-id="sec-inventario"', false)
             ->assertSee('"sec-ventas","dashboard","sec-inventario"', false);
    }
}
