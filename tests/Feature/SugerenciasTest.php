<?php

namespace Tests\Feature;

use App\Models\PermisoUsuario;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SugerenciasTest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_la_lista_de_la_fuente(): void
    {
        $admin = Usuario::factory()->admin()->create();
        Producto::factory()->create(['nombre' => 'Pan francés']);

        $this->actingAs($admin)->getJson(route('sugerencias', 'productos'))->assertOk()
            ->assertJsonFragment(['t' => 'Pan francés']);
        $this->actingAs($admin)->getJson(route('sugerencias', 'usuarios'))->assertOk()
            ->assertJsonFragment(['t' => $admin->nombre]);
        $this->actingAs($admin)->getJson(route('sugerencias', 'noexiste'))->assertNotFound();
    }

    public function test_respeta_los_permisos_de_cada_pantalla(): void
    {
        $u = Usuario::factory()->create();
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'ventas']);

        $this->actingAs($u->fresh())->getJson(route('sugerencias', 'productos'))->assertForbidden();
        $this->actingAs($u->fresh())->getJson(route('sugerencias', 'usuarios'))->assertForbidden();
    }

    public function test_los_buscadores_tienen_sugerencias_y_sin_autocompletar_del_navegador(): void
    {
        $this->withoutVite();
        $admin = Usuario::factory()->admin()->create();
        foreach (['productos.index', 'categorias.index', 'usuarios.index', 'materia-prima.index', 'clientes.index', 'proveedores.index', 'produccion.recetas', 'actividad.index', 'reportes.stock', 'reportes.kardex'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk()->assertSee('data-sugerencias=', false)->assertSee('autocomplete="off"', false);
        }
    }
}
