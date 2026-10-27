<?php

namespace Tests\Feature;

use App\Models\PermisoUsuario;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Permisos por PANTALLA: el admin elige, dentro de cada módulo, qué pantallas
 * puede usar cada usuario (p. ej. Inventario › Conteo Físico, sin Materia Prima).
 */
class PermisosPantallaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function usuarioCon(array $claves): Usuario
    {
        $u = Usuario::factory()->create();
        foreach ($claves as $c) {
            PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => $c]);
        }
        return $u->fresh();
    }

    public function test_con_una_pantalla_entra_solo_a_esa_pantalla_del_modulo(): void
    {
        $u = $this->usuarioCon(['inventario.conteo']);

        $this->actingAs($u)->get(route('conteo-fisico.index'))->assertOk();
        $this->actingAs($u)->get(route('materia-prima.index'))->assertForbidden();
        $this->actingAs($u)->get(route('movimientos.index'))->assertForbidden();
        $this->actingAs($u)->get(route('kardex.index'))->assertForbidden();
    }

    public function test_el_menu_muestra_solo_las_pantallas_permitidas(): void
    {
        $u = $this->usuarioCon(['inventario.conteo', 'reportes.stock']);

        $this->actingAs($u)->get(route('dashboard'))->assertOk()
            ->assertSee('data-menu-id="sec-inventario"', false)
            ->assertSee('data-menu-id="conteo-fisico"', false)
            ->assertDontSee('data-menu-id="materia-prima"', false)
            ->assertDontSee('data-menu-id="movimientos-productos"', false)
            ->assertSee('data-menu-id="reportes-stock"', false)
            ->assertDontSee('data-menu-id="reportes-kardex"', false)
            ->assertDontSee('data-menu-id="sec-compras"', false);
    }

    public function test_un_permiso_antiguo_de_modulo_completo_sigue_dando_todas_sus_pantallas(): void
    {
        $u = $this->usuarioCon(['inventario']);

        $this->actingAs($u)->get(route('materia-prima.index'))->assertOk();
        $this->actingAs($u)->get(route('kardex.index'))->assertOk();
        $this->actingAs($u)->get(route('compras.index'))->assertForbidden();
        $this->assertSame(
            ['inventario.materia_prima', 'inventario.movimientos', 'inventario.conteo', 'inventario.kardex'],
            $u->modulosAsignados()
        );
    }

    public function test_con_solo_recetas_entra_a_recetas_pero_no_a_produccion(): void
    {
        $soloRecetas = $this->usuarioCon(['produccion.recetas']);

        $this->actingAs($soloRecetas)->get(route('produccion.recetas'))->assertOk();
        $this->actingAs($soloRecetas)->get(route('produccion.index'))->assertForbidden();
    }

    public function test_el_admin_crea_un_usuario_eligiendo_pantallas(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $idRol = Rol::where('nombre', 'usuario')->value('id');

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'nombre' => 'Almacén 2', 'usuario' => 'almacen2', 'email' => 'a2@panaderia.com',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'id_rol' => $idRol, 'estado' => 'activo',
            'permisos' => ['inventario.materia_prima', 'inventario.conteo', 'ventas'],
        ])->assertRedirect(route('usuarios.index'));

        $nuevo = Usuario::where('usuario', 'almacen2')->first();
        $this->assertEqualsCanonicalizing(
            ['inventario.materia_prima', 'inventario.conteo', 'ventas'],
            $nuevo->permisos->pluck('modulo')->all()
        );
    }

    public function test_rechaza_claves_de_permiso_que_no_existen(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $idRol = Rol::where('nombre', 'usuario')->value('id');

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'nombre' => 'X', 'usuario' => 'x1', 'email' => 'x1@panaderia.com',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'id_rol' => $idRol, 'estado' => 'activo',
            'permisos' => ['inventario.inventado'],
        ])->assertSessionHasErrors('permisos.0');

        $this->assertNull(Usuario::where('usuario', 'x1')->first());
    }

    public function test_el_formulario_de_edicion_marca_las_pantallas_del_usuario(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $u = $this->usuarioCon(['compras.compras']);

        $html = $this->actingAs($admin)->get(route('usuarios.edit', $u))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="compras\.compras"[^>]*\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="compras\.proveedores"[^>]*\s+checked/', $html);
    }

    public function test_la_migracion_convierte_modulos_completos_en_todas_sus_pantallas(): void
    {
        $u = Usuario::factory()->create();
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'compras']);
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'ventas']);

        $migracion = require database_path('migrations/2025_01_01_000021_permisos_por_pantalla.php');
        $migracion->up();

        $this->assertEqualsCanonicalizing(
            ['compras.proveedores', 'compras.compras', 'compras.ordenes', 'ventas'],
            PermisoUsuario::where('id_usuario', $u->id)->pluck('modulo')->all()
        );
    }
}
