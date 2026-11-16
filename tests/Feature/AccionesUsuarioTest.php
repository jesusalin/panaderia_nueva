<?php

namespace Tests\Feature;

use App\Models\PermisoUsuario;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Acciones dentro de una pantalla que el admin le puede quitar a un usuario: por ejemplo,
 * un vendedor usa Productos para vender y registrar mermas, pero no crea ni edita productos.
 */
class AccionesUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create();
    }

    private function usuarioCon(array $claves): Usuario
    {
        $u = Usuario::factory()->create(['usuario' => 'vendedor1']);
        foreach ($claves as $c) PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => $c]);
        return $u->fresh();
    }

    public function test_sin_la_accion_no_puede_editar_pero_si_usar_la_pantalla_y_ajustar(): void
    {
        $p = Producto::factory()->create();
        $u = $this->usuarioCon(['catalogo.productos', 'ventas', 'sin:catalogo.productos.editar']);

        $this->assertTrue($u->hasPermiso('catalogo.productos'));
        $this->assertFalse($u->puede('catalogo.productos.editar'));
        $this->assertTrue($u->puede('catalogo.productos.ajustar'));

        $this->actingAs($u)->get(route('productos.index'))->assertOk()
            ->assertSee('const BLOQUEOS', false);          // oculta los botones de editar en la pantalla
        $this->actingAs($u)->from(route('productos.index'))->get(route('productos.edit', $p))
            ->assertRedirect(route('productos.index'))->assertSessionHas('error', fn ($m) => str_contains($m, 'crear, editar y eliminar productos'));
        $this->actingAs($u)->put(route('productos.update', $p), ['nombre' => 'Otro'])->assertRedirect();
        $this->assertNotSame('Otro', $p->fresh()->nombre);
        $this->actingAs($u)->delete(route('productos.destroy', $p))->assertRedirect();
        $this->assertNotNull($p->fresh());
        $this->actingAs($u)->get(route('productos.create'))->assertRedirect();

        // la merma / ajuste sí
        $this->actingAs($u)->get(route('productos.ajuste', $p))->assertOk();
    }

    public function test_con_todas_sus_acciones_no_se_ocultan_botones_y_el_admin_puede_todo(): void
    {
        $u = $this->usuarioCon(['catalogo.productos']);
        $this->assertTrue($u->puede('catalogo.productos.editar'));
        $this->actingAs($u)->get(route('productos.index'))->assertOk()->assertDontSee('const BLOQUEOS', false);
        $this->assertTrue($this->admin->puede('ventas.anular'));
        $this->assertSame([], $this->admin->accionesQuitadas());
        // sin la pantalla tampoco tiene la acción
        $this->assertFalse($u->puede('ventas.anular'));
    }

    public function test_el_admin_quita_acciones_desde_el_formulario(): void
    {
        $u = $this->usuarioCon(['catalogo.productos']);
        $datos = ['nombre' => $u->nombre, 'usuario' => $u->usuario, 'email' => $u->email, 'id_rol' => $u->id_rol, 'estado' => 'activo',
            'permisos' => ['catalogo.productos', 'ventas'], 'acciones_enviadas' => 1,
            // marcadas = las que SÍ puede; editar productos y anular ventas quedan sin marcar
            'acciones' => ['catalogo.productos.ajustar']];
        $this->actingAs($this->admin)->put(route('usuarios.update', $u), $datos)->assertRedirect();

        $u = $u->fresh();
        $this->assertEqualsCanonicalizing(['catalogo.productos.editar', 'ventas.anular'], $u->accionesQuitadas());
        // solo se guardan las de pantallas que tiene
        $this->assertFalse(PermisoUsuario::where('id_usuario', $u->id)->where('modulo', 'like', 'sin:clientes%')->exists());
        $this->actingAs($this->admin)->get(route('usuarios.edit', $u))->assertOk()->assertSee('Dentro de esta pantalla puede');
        $this->actingAs($this->admin)->get(route('usuarios.show', $u))->assertOk()->assertSee('No puede (aunque tenga la pantalla)')->assertSee('Anular ventas');

        // al volver a marcar todo, recupera las acciones
        $datos['acciones'] = array_keys(Usuario::clavesAccion());
        $this->actingAs($this->admin)->put(route('usuarios.update', $u), $datos);
        $this->assertSame([], $u->fresh()->accionesQuitadas());

        // un formulario sin la sección de acciones no cambia las que ya tenía quitadas
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'sin:ventas.anular']);
        unset($datos['acciones_enviadas'], $datos['acciones']);
        $this->actingAs($this->admin)->put(route('usuarios.update', $u), $datos);
        $this->assertSame(['ventas.anular'], $u->fresh()->accionesQuitadas());
    }

    public function test_acciones_de_otras_pantallas_tambien_se_bloquean(): void
    {
        $u = $this->usuarioCon(['clientes', 'reportes.cierre', 'sin:clientes.editar', 'sin:reportes.cierre.cerrar']);
        $this->actingAs($u)->get(route('clientes.index'))->assertOk();
        $this->actingAs($u)->post(route('clientes.store'), ['nombre' => 'Bodega X', 'tipo' => 'bodega'])->assertRedirect();
        $this->assertFalse(DB::table('clientes')->where('nombre', 'Bodega X')->exists());
        $this->actingAs($u)->get(route('reportes.cierre'))->assertOk();
        $this->actingAs($u)->post(route('reportes.cierre.guardar'), ['fecha' => today()->toDateString()])->assertRedirect();
        $this->assertSame(0, DB::table('cierres_inventario')->count());
        $this->actingAs($u)->postJson(route('reportes.cierre.guardar'), ['fecha' => today()->toDateString()])->assertForbidden();
    }

    public function test_produccion_sigue_mostrando_ver_por_pagina_con_todos(): void
    {
        $p = Producto::factory()->create();
        for ($i = 0; $i < 17; $i++) {
            DB::table('producciones')->insert(['id_producto' => $p->id, 'id_usuario' => $this->admin->id, 'cantidad' => 10, 'fecha' => today(), 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->actingAs($this->admin)->get(route('produccion.index', ['por' => 'todos']))->assertOk()
            ->assertSee('pp-ver', false)->assertSee('de 17 producciones');
    }
}
