<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\KardexProducto;
use App\Models\PermisoUsuario;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Producto $pan;
    private Producto $torta;
    private Cliente $general;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create();
        $cat = DB::table('categorias')->insertGetId(['nombre' => 'Panes', 'created_at' => now(), 'updated_at' => now()]);
        $this->pan   = Producto::create(['id_categoria' => $cat, 'nombre' => 'Pan francés', 'precio_venta' => 0.20, 'costo_produccion' => 0.08, 'stock_actual' => 100, 'stock_minimo' => 10, 'estado' => 'activo']);
        $this->torta = Producto::create(['id_categoria' => $cat, 'nombre' => 'Torta de chocolate', 'precio_venta' => 45, 'costo_produccion' => 0, 'stock_actual' => 3, 'stock_minimo' => 1, 'estado' => 'activo']);
        $this->general = Cliente::factory()->create(['nombre' => 'Cliente General']);
    }

    private function vender(array $lineas, array $extra = [])
    {
        return $this->actingAs($this->admin)->post(route('ventas.store'), $extra + [
            'id_cliente' => $this->general->id, 'tipo_pago' => 'efectivo',
            'productos' => collect($lineas)->map(fn ($c, $id) => ['id_producto' => $id, 'cantidad' => $c])->values()->all(),
        ]);
    }

    public function test_el_precio_incluye_igv_y_se_calcula_el_vuelto(): void
    {
        // 11 panes a S/ 0.20 = S/ 2.20 (antes se cobraba S/ 2.60 sumándole 18 % encima)
        $this->vender([$this->pan->id => 11], ['monto_recibido' => 5])->assertSessionHasNoErrors();
        $v = Venta::firstOrFail();
        $this->assertEquals(2.20, $v->total);
        $this->assertEquals(1.86, $v->subtotal);
        $this->assertEquals(0.34, $v->igv);
        $this->assertEquals(5, $v->monto_recibido);
        $this->assertEquals(2.80, $v->vuelto);
        $this->assertSame('V-' . str_pad((string) $v->id, 6, '0', STR_PAD_LEFT), $v->numero_venta);
        $this->assertSame(89, $this->pan->fresh()->stock_actual);
        $this->assertNotNull(KardexProducto::where('motivo', 'venta')->where('referencia_id', $v->id)->first());
    }

    public function test_lo_recibido_debe_alcanzar_y_no_se_repiten_productos(): void
    {
        $this->vender([$this->torta->id => 1], ['monto_recibido' => 40])->assertSessionHasErrors('monto_recibido');
        $this->assertSame(0, Venta::count());
        $this->assertSame(3, $this->torta->fresh()->stock_actual);

        $this->actingAs($this->admin)->post(route('ventas.store'), [
            'tipo_pago' => 'yape', 'productos' => [['id_producto' => $this->pan->id, 'cantidad' => 1], ['id_producto' => $this->pan->id, 'cantidad' => 2]],
        ])->assertSessionHasErrors('productos.1.id_producto');

        // Yape: guarda el n° de operación y no hay vuelto
        $this->vender([$this->pan->id => 5], ['tipo_pago' => 'yape', 'numero_operacion' => '123456', 'monto_recibido' => 99])->assertSessionHasNoErrors();
        $v = Venta::firstOrFail();
        $this->assertSame('123456', $v->numero_operacion);
        $this->assertNull($v->monto_recibido);
        $this->assertNull($v->vuelto);
    }

    public function test_la_venta_rapida_vuelve_a_productos_con_la_ultima_venta(): void
    {
        $r = $this->actingAs($this->admin)->from(route('productos.index', ['categoria' => 1]))
            ->post(route('ventas.store'), ['tipo_pago' => 'efectivo', 'monto_recibido' => 1, 'volver' => 'productos',
                'productos' => [['id_producto' => $this->pan->id, 'cantidad' => 2]]]);
        $v = Venta::firstOrFail();
        $r->assertRedirect(route('productos.index', ['categoria' => 1]))->assertSessionHas('ultima_venta', $v->id);
        $this->actingAs($this->admin)->withSession(['ultima_venta' => $v->id])->get(route('productos.index'))
            ->assertOk()->assertSee("Venta {$v->numero_venta} registrada")->assertSee('Vuelto: <b>S/ 0.60</b>', false)
            ->assertSee('Venta rápida')->assertSee('Torta de chocolate');

        // Si algo falla vuelve a Productos con el error (el carrito se recupera)
        $this->actingAs($this->admin)->from(route('productos.index'))
            ->post(route('ventas.store'), ['tipo_pago' => 'efectivo', 'volver' => 'productos', 'productos' => [['id_producto' => $this->torta->id, 'cantidad' => 9]]])
            ->assertRedirect(route('productos.index'))->assertSessionHasErrors('error');

        // La antigua "Nueva venta" lleva a Productos
        $this->actingAs($this->admin)->get(route('ventas.create'))->assertRedirect(route('productos.index'));
        $this->actingAs($this->admin)->get(route('ventas.index'))->assertDontSee('Nueva venta');
    }

    public function test_anular_pide_motivo_devuelve_stock_y_no_se_borra(): void
    {
        $this->vender([$this->pan->id => 10]);
        $v = Venta::firstOrFail();
        $this->actingAs($this->admin)->put(route('ventas.anular', $v), [])->assertSessionHasErrors('motivo_anulacion');
        $this->assertSame('completada', $v->fresh()->estado);

        $this->actingAs($this->admin)->put(route('ventas.anular', $v), ['motivo_anulacion' => 'Se cobró dos veces'])->assertRedirect(route('ventas.show', $v));
        $v->refresh();
        $this->assertSame('anulada', $v->estado);
        $this->assertSame('Se cobró dos veces', $v->motivo_anulacion);
        $this->assertSame($this->admin->id, $v->id_usuario_anula);
        $this->assertNotNull($v->anulada_at);
        $this->assertSame(100, $this->pan->fresh()->stock_actual);
        $this->assertStringContainsString('Se cobró dos veces', KardexProducto::where('motivo', 'devolucion')->value('observacion'));

        // Ya no existe la ruta para eliminar ventas
        $this->actingAs($this->admin)->delete('/ventas/' . $v->id)->assertStatus(405);
        $this->actingAs($this->admin)->get(route('ventas.show', $v))->assertOk()->assertSee('Venta anulada')->assertSee('Se cobró dos veces');
    }

    public function test_pantallas_de_ventas(): void
    {
        $this->vender([$this->pan->id => 5, $this->torta->id => 1], ['monto_recibido' => 50]);
        $v = Venta::firstOrFail();
        $this->actingAs($this->admin)->get(route('ventas.index'))->assertOk()->assertSee('Caja de hoy')->assertSee('S/ 46.00')->assertSee($v->numero_venta);
        $this->actingAs($this->admin)->get(route('ventas.index', ['buscar' => 'torta']))->assertOk()->assertSee($v->numero_venta);
        $this->actingAs($this->admin)->get(route('ventas.index', ['buscar' => 'zzz']))->assertOk()->assertSee('No hay ventas con estos filtros');
        $this->actingAs($this->admin)->get(route('ventas.index', ['tipo_pago' => 'yape']))->assertOk()->assertDontSee($v->numero_venta . '</span>', false);
        $this->actingAs($this->admin)->get(route('ventas.index', ['periodo' => 'ayer']))->assertOk();
        $this->actingAs($this->admin)->get(route('ventas.show', $v))->assertOk()->assertSee('IGV (18 %) incluido')->assertSee('S/ 4.00');   // vuelto
        $this->actingAs($this->admin)->get(route('ventas.show', $v), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('Abrir venta');
        $this->actingAs($this->admin)->get(route('ventas.ticket', $v))->assertOk()->assertSee('PANADERÍA MURUWASI')->assertSee('46.00');
        $this->actingAs($this->admin)->get(route('ventas.pdf', $v))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('ventas.exportar-excel', ['periodo' => 'hoy']))->assertOk();
    }

    public function test_reporte_de_ventas(): void
    {
        $this->vender([$this->pan->id => 50]);                                    // S/ 10.00
        $this->vender([$this->torta->id => 1], ['tipo_pago' => 'yape']);          // S/ 45.00
        $this->vender([$this->pan->id => 5]);                                     // anulada
        $this->actingAs($this->admin)->put(route('ventas.anular', Venta::latest('id')->first()), ['motivo_anulacion' => 'prueba']);

        $r = $this->actingAs($this->admin)->get(route('reportes.ventas'))->assertOk();
        // 55.00 vendidos; pan: 10 / 1.18 = 8.47 sin IGV − 50 × 0.08 = 4.47 de ganancia (53 %)
        $r->assertSee('S/ 55.00')->assertSee('Torta de chocolate')->assertSee('S/ 4.47')->assertSee('53 %')->assertSee('sin costo')->assertSee('Yape');
        $r->assertSee('data-menu-id="reportes-ventas"', false);
        $this->actingAs($this->admin)->get(route('reportes.ventas', ['periodo' => 'todo']))->assertOk();
        $this->actingAs($this->admin)->get(route('reportes.ventas.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('reportes.ventas.excel'))->assertOk()->assertDownload('reporte-ventas-' . today()->format('Y-m-d') . '.xlsx');
        $this->actingAs($this->admin)->get(route('reportes.index'))->assertOk()->assertSee('Ventas del mes');

        // Permiso propio
        $u = Usuario::factory()->create();
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'reportes.stock']);
        $this->actingAs($u->fresh())->get(route('reportes.ventas'))->assertForbidden();
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'reportes.ventas']);
        $this->actingAs($u->fresh())->get(route('reportes.ventas'))->assertOk();
    }
}
