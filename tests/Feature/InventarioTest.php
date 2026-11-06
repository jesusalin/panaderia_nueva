<?php

namespace Tests\Feature;

use App\Models\ConteoFisico;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private int $kg;
    private MateriaPrima $harina;
    private MateriaPrima $sal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create();
        $this->kg = DB::table('unidades_medida')->insertGetId(['nombre' => 'kilogramo', 'abreviatura' => 'kg']);
        $this->harina = MateriaPrima::create(['nombre' => 'Harina', 'id_unidad' => $this->kg, 'stock_actual' => 10, 'stock_minimo' => 2, 'costo_unitario' => 3, 'estado' => 'activo', 'es_control' => true]);
        $this->sal    = MateriaPrima::create(['nombre' => 'Sal', 'id_unidad' => $this->kg, 'stock_actual' => 1, 'stock_minimo' => 2, 'costo_unitario' => 1, 'estado' => 'activo']);
    }

    private function datos(array $extra = []): array
    {
        return array_merge(['nombre' => 'Azúcar', 'id_unidad' => $this->kg, 'stock_minimo' => 1, 'costo_unitario' => 2.5, 'estado' => 'activo'], $extra);
    }

    public function test_el_stock_inicial_queda_como_movimiento(): void
    {
        $this->actingAs($this->admin)->post(route('materia-prima.store'), $this->datos(['stock_actual' => 5]))->assertRedirect();
        $m = MateriaPrima::where('nombre', 'Azúcar')->firstOrFail();
        $this->assertEquals(5, (float) $m->stock_actual);
        $this->assertDatabaseHas('movimientos_inventario', ['id_materia' => $m->id, 'tipo' => 'entrada', 'motivo' => 'ajuste_manual']);
    }

    public function test_editar_no_cambia_el_stock(): void
    {
        $this->actingAs($this->admin)->put(route('materia-prima.update', $this->harina), $this->datos(['nombre' => 'Harina extra', 'stock_actual' => 999]))
            ->assertRedirect(route('materia-prima.index'));
        $this->harina->refresh();
        $this->assertSame('Harina extra', $this->harina->nombre);
        $this->assertEquals(10, (float) $this->harina->stock_actual);
    }

    public function test_merma_en_gramos_y_correccion_por_conteo(): void
    {
        $this->actingAs($this->admin)->post(route('materia-prima.ajuste.store', $this->harina), [
            'tipo_ajuste' => 'merma', 'motivo_merma' => 'roto', 'cantidad' => 500, 'unidad' => 'sub',
        ])->assertRedirect(route('materia-prima.index'));
        $this->assertEquals(9.5, (float) $this->harina->fresh()->stock_actual);
        $this->assertDatabaseHas('movimientos_inventario', ['id_materia' => $this->harina->id, 'tipo' => 'salida', 'motivo' => 'merma']);

        // corrección sin detalle → se pide
        $this->actingAs($this->admin)->post(route('materia-prima.ajuste.store', $this->harina), [
            'tipo_ajuste' => 'conteo', 'stock_real' => 8, 'unidad' => 'base',
        ])->assertSessionHasErrors('observacion');

        $this->actingAs($this->admin)->post(route('materia-prima.ajuste.store', $this->harina), [
            'tipo_ajuste' => 'conteo', 'stock_real' => 8, 'unidad' => 'base', 'observacion' => 'conté los sacos',
        ])->assertRedirect();
        $this->assertEquals(8, (float) $this->harina->fresh()->stock_actual);
        $this->assertDatabaseHas('movimientos_inventario', ['id_materia' => $this->harina->id, 'tipo' => 'ajuste', 'motivo' => 'ajuste_manual']);
    }

    public function test_no_se_puede_mermar_mas_de_lo_que_hay(): void
    {
        $this->actingAs($this->admin)->post(route('materia-prima.ajuste.store', $this->sal), [
            'tipo_ajuste' => 'merma', 'motivo_merma' => 'vencido', 'cantidad' => 5, 'unidad' => 'base',
        ])->assertSessionHas('error');
        $this->assertEquals(1, (float) $this->sal->fresh()->stock_actual);
    }

    public function test_pantallas_de_inventario_cargan(): void
    {
        MovimientoInventario::create(['id_materia' => $this->harina->id, 'id_usuario' => $this->admin->id, 'tipo' => 'salida', 'motivo' => 'produccion',
            'cantidad' => 1.4, 'stock_antes' => 11.4, 'stock_despues' => 10, 'observacion' => 'Producción', 'created_at' => now()]);

        $this->actingAs($this->admin)->get(route('materia-prima.index'))->assertOk()->assertSee('Harina')->assertSee('Alcanza para');
        $this->actingAs($this->admin)->get(route('materia-prima.index', ['stock' => 'bajo']))->assertOk()->assertSee('Sal')->assertDontSee('>Harina<', false);
        $this->actingAs($this->admin)->get(route('materia-prima.index', ['orden' => 'dias']))->assertOk();
        $this->actingAs($this->admin)->get(route('materia-prima.show', $this->harina))->assertOk()->assertSee('100 g/día', false);
        $this->actingAs($this->admin)->get(route('materia-prima.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('materia-prima.edit', $this->harina))->assertOk();
        $this->actingAs($this->admin)->get(route('materia-prima.ajuste', $this->harina))->assertOk();
        $this->actingAs($this->admin)->get(route('movimientos.index'))->assertOk()->assertSee('Producción');
        $this->actingAs($this->admin)->get(route('movimientos.index', ['motivo' => 'merma', 'periodo' => 'hoy', 'buscar' => 'har']))->assertOk();
        $this->actingAs($this->admin)->get(route('kardex.index', ['periodo' => '30']))->assertOk();
    }

    public function test_conteo_de_control_calcula_el_per(): void
    {
        $this->actingAs($this->admin)->get(route('conteo-fisico.index'))->assertOk()->assertSee('Harina')->assertDontSee('>Sal<', false);
        $this->actingAs($this->admin)->get(route('conteo-fisico.index', ['modo' => 'todos']))->assertOk()->assertSee('Sal');

        $r = $this->actingAs($this->admin)->post(route('conteo-fisico.store'), [
            'modo' => 'todos',
            'conteo' => [
                $this->harina->id => ['stock_fisico' => 9500, 'unidad' => 'sub'],
                $this->sal->id    => ['stock_fisico' => 1, 'unidad' => 'base'],
            ],
        ]);
        $lote = ConteoFisico::value('lote');
        $r->assertRedirect(route('conteo-fisico.detalle', $lote));
        $this->assertEquals(9.5, (float) $this->harina->fresh()->stock_actual);
        $this->assertEquals(1, (float) $this->sal->fresh()->stock_actual);

        $this->actingAs($this->admin)->get(route('conteo-fisico.detalle', $lote))->assertOk()->assertSee('50 %')->assertSee('Stock corregido');
        $this->actingAs($this->admin)->get(route('conteo-fisico.historial'))->assertOk()->assertSee('PER 50');
    }

    public function test_no_se_repite_el_nombre_del_insumo(): void
    {
        $this->actingAs($this->admin)->post(route('materia-prima.store'), $this->datos(['nombre' => 'Harina', 'stock_actual' => 0]))
            ->assertSessionHasErrors(['nombre' => 'Ya existe un insumo con ese nombre.']);
        // al editar puede conservar su propio nombre
        $this->actingAs($this->admin)->put(route('materia-prima.update', $this->harina), $this->datos(['nombre' => 'Harina']))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->put(route('materia-prima.update', $this->harina), $this->datos(['nombre' => 'Sal']))->assertSessionHasErrors('nombre');
    }

    public function test_formulario_y_conteo_solo_desde_el_boton(): void
    {
        MovimientoInventario::create(['id_materia' => $this->harina->id, 'id_usuario' => $this->admin->id, 'tipo' => 'salida', 'motivo' => 'produccion',
            'cantidad' => 1.4, 'stock_antes' => 11.4, 'stock_despues' => 10, 'observacion' => 'Producción', 'created_at' => now()]);

        $this->actingAs($this->admin)->get(route('materia-prima.create'))->assertOk()->assertSee('mpNombres', false)->assertSee('Harina');
        $this->actingAs($this->admin)->get(route('materia-prima.edit', $this->harina))->assertOk()
            ->assertSee('Sugerido: 3 días de consumo')->assertSee('no se convierten');

        $this->actingAs($this->admin)->get(route('materia-prima.index'))->assertOk()
            ->assertDontSee('data-menu-id="conteo-fisico"', false)
            ->assertSee(route('conteo-fisico.index'), false);
        $this->actingAs($this->admin)->get(route('conteo-fisico.index', ['q' => 'har']))->assertOk()
            ->assertSee('id="cfBuscar"', false)->assertSee('value="har"', false)->assertSee('Por contar');
    }
}
