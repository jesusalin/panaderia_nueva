<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\MateriaPrima;
use App\Models\OrdenAutomatica;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComprasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Proveedor $molino;
    private Proveedor $granja;
    private MateriaPrima $harina;
    private MateriaPrima $huevo;
    private MateriaPrima $esencia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create();
        $kg  = DB::table('unidades_medida')->insertGetId(['nombre' => 'kilogramo', 'abreviatura' => 'kg']);
        $und = DB::table('unidades_medida')->insertGetId(['nombre' => 'unidad', 'abreviatura' => 'und']);
        $this->molino = Proveedor::create(['nombre' => 'Molinos del Sur S.A.C.', 'ruc' => '20123456789', 'telefono' => '987654321', 'estado' => 'activo']);
        $this->granja = Proveedor::create(['nombre' => 'Granja Feliz', 'estado' => 'activo']);
        $this->harina = MateriaPrima::create(['nombre' => 'Harina', 'id_unidad' => $kg, 'id_proveedor' => $this->molino->id, 'stock_actual' => 10, 'stock_minimo' => 20, 'cantidad_reposicion' => 50, 'costo_unitario' => 3, 'estado' => 'activo']);
        $this->huevo  = MateriaPrima::create(['nombre' => 'Huevo', 'id_unidad' => $und, 'id_proveedor' => $this->granja->id, 'stock_actual' => 30, 'stock_minimo' => 60, 'costo_unitario' => 0.5, 'estado' => 'activo']);
        $this->esencia = MateriaPrima::create(['nombre' => 'Esencia', 'id_unidad' => $und, 'id_proveedor' => null, 'stock_actual' => 0, 'stock_minimo' => 2, 'costo_unitario' => 8, 'estado' => 'activo']);
    }

    private function compra(array $extra = []): array
    {
        return array_merge([
            'id_proveedor' => $this->molino->id, 'fecha_compra' => today()->toDateString(), 'numero_doc' => 'F001-000001',
            'materias' => [['id_materia' => $this->harina->id, 'cantidad' => 50, 'unidad' => 'base', 'precio_unitario' => 3.2]],
        ], $extra);
    }

    public function test_registrar_compra_calcula_totales_y_no_toca_el_stock(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra())->assertRedirect();
        $c = Compra::firstOrFail();
        $this->assertSame('pendiente', $c->estado);
        $this->assertEquals(160, $c->subtotal);
        $this->assertEquals(28.8, $c->igv);
        $this->assertEquals(188.8, $c->total);
        $this->assertEquals(10, (float) $this->harina->fresh()->stock_actual);
    }

    public function test_precios_con_igv_y_cantidad_en_gramos(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra([
            'incluye_igv' => 1,
            'materias' => [['id_materia' => $this->harina->id, 'cantidad' => 2500, 'unidad' => 'sub', 'precio_unitario' => 4.72]],
        ]))->assertRedirect();
        $c = Compra::with('detalles')->firstOrFail();
        $this->assertEquals(2.5, $c->detalles->first()->cantidad);
        $this->assertEquals(11.8, $c->total);
        $this->assertEquals(10, $c->subtotal);
        $this->assertEquals(1.8, $c->igv);
    }

    public function test_no_se_repite_la_factura_ni_el_insumo(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra())->assertRedirect();
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra())->assertSessionHasErrors('numero_doc');
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra(['numero_doc' => 'F001-2', 'materias' => [
            ['id_materia' => $this->harina->id, 'cantidad' => 1, 'precio_unitario' => 3],
            ['id_materia' => $this->harina->id, 'cantidad' => 2, 'precio_unitario' => 3],
        ]]))->assertSessionHasErrors('materias.1.id_materia');
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra(['numero_doc' => 'F001-3', 'fecha_compra' => today()->addDay()->toDateString()]))
            ->assertSessionHasErrors('fecha_compra');
    }

    public function test_recibir_completo_sube_stock_y_costo(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra());
        $c = Compra::firstOrFail();
        $this->actingAs($this->admin)->get(route('compras.recibir.form', $c))->assertOk()->assertSee('¿Cuánto llegó?');
        $this->actingAs($this->admin)->put(route('compras.recibir', $c))->assertRedirect(route('compras.show', $c));

        $c->refresh();
        $this->assertSame('recibida', $c->estado);
        $this->assertNotNull($c->recibida_at);
        $this->assertEquals($this->admin->id, $c->id_usuario_recibe);
        $this->assertEquals(60, (float) $this->harina->fresh()->stock_actual);
        // costo promedio ponderado: (10 kg × 3.00 + 50 kg × 3.20) / 60 kg = 3.1667
        $this->assertEqualsWithDelta(3.1667, (float) $this->harina->fresh()->costo_unitario, 0.0001);
        $this->assertDatabaseHas('movimientos_inventario', ['id_materia' => $this->harina->id, 'tipo' => 'entrada', 'motivo' => 'compra', 'referencia_id' => $c->id]);
        // ya no se puede recibir otra vez
        $this->actingAs($this->admin)->put(route('compras.recibir', $c))->assertSessionHasErrors();
        $this->assertEquals(60, (float) $this->harina->fresh()->stock_actual);
    }

    public function test_recibir_con_otra_cantidad_pide_nota_y_recalcula(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra());
        $c = Compra::with('detalles')->firstOrFail();
        $d = $c->detalles->first();

        $this->actingAs($this->admin)->put(route('compras.recibir', $c), ['recibido' => [$d->id => 40]])->assertSessionHasErrors('nota_recepcion');
        $this->actingAs($this->admin)->put(route('compras.recibir', $c), ['recibido' => [$d->id => 40], 'nota_recepcion' => 'Faltaron 2 sacos'])->assertRedirect();

        $c->refresh();
        $this->assertEquals(40, $c->detalles->first()->cantidad_recibida);
        $this->assertEquals(50, (float) $this->harina->fresh()->stock_actual);
        $this->assertEquals(round(128 * 1.18, 2), $c->total);
        $this->assertTrue($c->recepcion_incompleta);
        $this->actingAs($this->admin)->get(route('compras.show', $c))->assertOk()->assertSee('faltó')->assertSee('Faltaron 2 sacos');
    }

    public function test_registrar_y_recibir_de_una_vez(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra(['recibir_ahora' => 1]))->assertRedirect();
        $this->assertSame('recibida', Compra::firstOrFail()->estado);
        $this->assertEquals(60, (float) $this->harina->fresh()->stock_actual);
    }

    public function test_anular_pide_motivo_y_devuelve_el_stock(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra(['recibir_ahora' => 1]));
        $c = Compra::firstOrFail();
        $this->actingAs($this->admin)->put(route('compras.anular', $c))->assertSessionHasErrors('motivo_anulacion');
        $this->actingAs($this->admin)->put(route('compras.anular', $c), ['motivo_anulacion' => 'Se registró dos veces'])->assertRedirect();

        $c->refresh();
        $this->assertSame('anulada', $c->estado);
        $this->assertSame('Se registró dos veces', $c->motivo_anulacion);
        $this->assertEquals(10, (float) $this->harina->fresh()->stock_actual);
        $this->assertDatabaseHas('movimientos_inventario', ['id_materia' => $this->harina->id, 'tipo' => 'salida', 'motivo' => 'devolucion']);
    }

    public function test_pantallas_de_compras_cargan(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra());
        $c = Compra::firstOrFail();
        $this->actingAs($this->admin)->get(route('compras.index'))->assertOk()->assertSee('Por recibir')->assertSee('Molinos del Sur');
        $this->actingAs($this->admin)->get(route('compras.index', ['buscar' => 'harina', 'periodo' => 'mes', 'estado' => 'pendiente']))->assertOk()->assertSee($c->codigo);
        $this->actingAs($this->admin)->get(route('compras.index', ['estado' => 'atrasada']))->assertOk();
        $this->actingAs($this->admin)->get(route('compras.create'))->assertOk()->assertSee('ncInsumos', false);
        $this->actingAs($this->admin)->get(route('compras.create', ['materia' => $this->harina->id]))->assertOk();
        $this->actingAs($this->admin)->get(route('compras.create', ['repetir' => $c->id]))->assertOk();
        $this->actingAs($this->admin)->get(route('compras.show', $c))->assertOk()->assertSee('Recibir pedido')->assertSee('wa.me/51987654321', false);
    }

    public function test_proveedores_ficha_y_validacion_de_ruc(): void
    {
        $this->actingAs($this->admin)->get(route('proveedores.index'))->assertOk()->assertSee('Molinos del Sur')->assertSee('sin proveedor');
        $this->actingAs($this->admin)->get(route('proveedores.show', $this->molino))->assertOk()->assertSee('Harina');
        $this->actingAs($this->admin)->get(route('proveedores.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('proveedores.edit', $this->molino))->assertOk();

        $this->actingAs($this->admin)->post(route('proveedores.store'), ['nombre' => 'Lácteos', 'ruc' => '123'])->assertSessionHasErrors('ruc');
        $this->actingAs($this->admin)->post(route('proveedores.store'), ['nombre' => 'Lácteos', 'ruc' => '20123456789'])->assertSessionHasErrors('ruc');
        $this->actingAs($this->admin)->post(route('proveedores.store'), ['nombre' => 'granja feliz'])->assertSessionHasErrors('nombre');
        $r = $this->actingAs($this->admin)->post(route('proveedores.store'), ['nombre' => 'Lácteos Andinos', 'ruc' => '20 555 666 777', 'telefono' => '912 345 678']);
        $p = Proveedor::where('nombre', 'Lácteos Andinos')->firstOrFail();
        $r->assertRedirect(route('proveedores.show', $p));
        $this->assertSame('20555666777', $p->ruc);
        $this->assertSame('51912345678', $p->whatsapp);
    }

    public function test_ordenes_automaticas_se_generan_solas_y_se_agrupan_por_proveedor(): void
    {
        $this->actingAs($this->admin)->get(route('ordenes-automaticas.index'))->assertOk()->assertSee('Harina')->assertSee('Sin proveedor');
        $this->assertSame(3, OrdenAutomatica::where('estado', 'pendiente')->count());

        $harina = OrdenAutomatica::where('id_materia', $this->harina->id)->first();
        $huevo  = OrdenAutomatica::where('id_materia', $this->huevo->id)->first();
        $esencia = OrdenAutomatica::where('id_materia', $this->esencia->id)->first();
        $this->assertEquals(50, $harina->cantidad_sugerida);

        // la que no tiene proveedor no se puede convertir
        $this->actingAs($this->admin)->post(route('ordenes-automaticas.convertir-varias'), ['ordenes' => [$esencia->id]])->assertSessionHasErrors('ordenes');

        $this->actingAs($this->admin)->post(route('ordenes-automaticas.convertir-varias'), [
            'ordenes' => [$harina->id, $huevo->id], 'cantidades' => [$harina->id => 40, $huevo->id => 90],
        ])->assertRedirect(route('compras.index', ['estado' => 'pendiente']));

        $this->assertSame(2, Compra::count());   // una por proveedor
        $this->assertEquals(40, Compra::where('id_proveedor', $this->molino->id)->first()->detalles->first()->cantidad);
        $this->assertSame('convertida', $harina->fresh()->estado);

        // con la compra en camino no se vuelve a generar; descartada tampoco
        $this->actingAs($this->admin)->post(route('ordenes-automaticas.descartar', $esencia))->assertRedirect();
        $this->actingAs($this->admin)->get(route('ordenes-automaticas.index'))->assertOk()->assertSee('Todo al día');
        $this->assertSame(0, OrdenAutomatica::where('estado', 'pendiente')->count());

        // si se anula la compra, su orden vuelve a pendientes
        $compra = Compra::where('id_proveedor', $this->molino->id)->first();
        $this->actingAs($this->admin)->put(route('compras.anular', $compra), ['motivo_anulacion' => 'No tenían stock']);
        $this->assertSame('pendiente', $harina->fresh()->estado);
        $this->actingAs($this->admin)->get(route('ordenes-automaticas.index', ['estado' => 'todas']))->assertOk();
    }

    public function test_ordenes_automaticas_se_abren_desde_compras(): void
    {
        $this->actingAs($this->admin)->get(route('compras.index'))->assertOk()
            ->assertDontSee('data-menu-id="ordenes-automaticas"', false)
            ->assertSee(route('ordenes-automaticas.index'), false);
        $this->actingAs($this->admin)->get(route('ordenes-automaticas.index'))->assertOk()->assertSee('fa-arrow-left', false);
    }

    public function test_anular_saca_la_compra_del_costo_promedio(): void
    {
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra(['recibir_ahora' => 1]));
        $this->assertEqualsWithDelta(3.1667, (float) $this->harina->fresh()->costo_unitario, 0.0001);
        $this->actingAs($this->admin)->put(route('compras.anular', Compra::first()), ['motivo_anulacion' => 'Error de registro']);
        $this->assertEqualsWithDelta(3.0, (float) $this->harina->fresh()->costo_unitario, 0.001);
    }

    public function test_dos_proveedores_para_un_insumo(): void
    {
        // Le compramos harina a los dos: al molino a 3.20 y a la granja a 2.90
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra(['recibir_ahora' => 1]));
        $this->actingAs($this->admin)->post(route('compras.store'), $this->compra(['id_proveedor' => $this->granja->id, 'numero_doc' => 'B-1', 'recibir_ahora' => 1,
            'materias' => [['id_materia' => $this->harina->id, 'cantidad' => 10, 'precio_unitario' => 2.9]]]));

        $precios = MateriaPrima::preciosPorProveedor([$this->harina->id])[$this->harina->id];
        $this->assertSame($this->granja->id, $precios->first()['id_proveedor']);   // el más barato primero
        $this->assertEquals(2.9, $precios->first()['precio']);

        $this->actingAs($this->admin)->get(route('materia-prima.show', $this->harina))->assertOk()
            ->assertSee('Precios por proveedor')->assertSee('más barato')->assertSee('principal');
        // Nueva compra a la granja propone lo que cobra la granja
        $this->actingAs($this->admin)->get(route('compras.create', ['materia' => $this->harina->id, 'proveedor' => $this->granja->id]))->assertOk()
            ->assertSee('"precio":2.9', false);

        // La orden automática de harina se puede pedir a la granja aunque su principal sea el molino
        $this->harina->update(['stock_actual' => 5]);
        $this->actingAs($this->admin)->get(route('ordenes-automaticas.index'))->assertOk()->assertSee('★ más barato');
        $orden = OrdenAutomatica::where('id_materia', $this->harina->id)->where('estado', 'pendiente')->firstOrFail();
        $this->actingAs($this->admin)->post(route('ordenes-automaticas.convertir-varias'), [
            'ordenes' => [$orden->id], 'proveedores' => [$orden->id => $this->granja->id],
        ])->assertRedirect();
        $compra = Compra::latest('id')->first();
        $this->assertSame($this->granja->id, $compra->id_proveedor);
        $this->assertEquals(2.9, $compra->detalles->first()->precio_unitario);
        $this->assertSame($this->granja->id, $orden->fresh()->id_proveedor);

        // Y una orden sin proveedor principal también se puede pedir eligiendo uno
        $esencia = OrdenAutomatica::where('id_materia', $this->esencia->id)->firstOrFail();
        $this->actingAs($this->admin)->post(route('ordenes-automaticas.convertir-varias'), [
            'ordenes' => [$esencia->id], 'proveedores' => [$esencia->id => $this->molino->id],
        ])->assertRedirect();
        $this->assertSame('convertida', $esencia->fresh()->estado);
    }
}
