<?php

namespace Tests\Feature;

use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\Usuario;
use App\Support\PlanProduccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProduccionRecetasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private MateriaPrima $harina;
    private MateriaPrima $leche;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create();
        $kg = DB::table('unidades_medida')->insertGetId(['nombre' => 'kilogramo', 'abreviatura' => 'kg']);
        $l  = DB::table('unidades_medida')->insertGetId(['nombre' => 'litro', 'abreviatura' => 'L']);
        $this->harina = MateriaPrima::create(['nombre' => 'Harina', 'id_unidad' => $kg, 'stock_actual' => 2.5, 'stock_minimo' => 1, 'costo_unitario' => 3, 'estado' => 'activo']);
        $this->leche  = MateriaPrima::create(['nombre' => 'Leche',  'id_unidad' => $l,  'stock_actual' => 10,  'stock_minimo' => 1, 'costo_unitario' => 4, 'estado' => 'activo']);
    }

    /** Pan: 1 kg de harina + 0.5 L de leche rinden 100 panes. */
    private function pan(array $extra = []): Producto
    {
        $p = Producto::factory()->create(array_merge(['nombre' => 'Pan', 'precio_venta' => 0.30, 'stock_actual' => 5, 'stock_minimo' => 40], $extra));
        $r = Receta::create(['id_producto' => $p->id, 'rendimiento' => 100]);
        RecetaDetalle::create(['id_receta' => $r->id, 'id_materia' => $this->harina->id, 'cantidad' => 1]);
        RecetaDetalle::create(['id_receta' => $r->id, 'id_materia' => $this->leche->id, 'cantidad' => 0.5]);
        return $p->fresh();
    }

    public function test_maximo_y_costo_de_la_tanda(): void
    {
        $p = $this->pan();
        $max = PlanProduccion::maximo($p);
        $this->assertSame(250, $max['max']);          // 2.5 kg de harina → 250 panes
        $this->assertSame('Harina', $max['limita']);
        $this->assertSame(5.0, PlanProduccion::costo($p, 100));   // 1 kg × 3 + 0.5 L × 4
    }

    public function test_que_producir_hoy_sugiere_tandas_completas_sin_pasar_lo_que_alcanza(): void
    {
        $p = $this->pan();
        $s = PlanProduccion::sugerencias()->firstWhere('producto.id', $p->id);
        $this->assertNotNull($s);
        $this->assertSame(0, $s->sugerido % 100);                  // tandas completas
        $this->assertLessThanOrEqual(250, $s->producir);
        $this->actingAs($this->admin)->get(route('produccion.create'))->assertOk()->assertSee('¿Qué producir hoy?');
    }

    public function test_mensaje_claro_cuando_no_alcanzan_los_insumos(): void
    {
        $p = $this->pan();
        $this->actingAs($this->admin)->from(route('produccion.create'))
            ->post(route('produccion.store'), ['id_producto' => $p->id, 'cantidad' => 300, 'fecha' => now()->toDateString()])
            ->assertSessionHasErrors(['id_producto' => 'No alcanzan los insumos: Harina (necesitas 3 kg, hay 2.5 kg). Con lo que hay puedes producir hasta 250 unidades.']);
        $this->assertSame(2.5, $this->harina->fresh()->stock_actual);
    }

    public function test_producir_descuenta_y_suma_stock(): void
    {
        $p = $this->pan();
        $this->actingAs($this->admin)->post(route('produccion.store'), ['id_producto' => $p->id, 'cantidad' => 100, 'fecha' => now()->toDateString()])
            ->assertRedirect(route('produccion.index'))->assertSessionHas('success');
        $this->assertSame(1.5, $this->harina->fresh()->stock_actual);
        $this->assertSame(9.5, $this->leche->fresh()->stock_actual);
        $this->assertSame(105, (int) $p->fresh()->stock_actual);
    }

    public function test_el_formulario_llega_con_el_producto_elegido(): void
    {
        $p = $this->pan();
        $this->actingAs($this->admin)->get(route('produccion.create', ['producto' => $p->id, 'cantidad' => 200]))
            ->assertOk()->assertSee('value="' . $p->id . '" checked', false)->assertSee('value="200"', false);
    }

    public function test_receta_en_gramos_y_mililitros_se_guarda_en_kg_y_l(): void
    {
        $p = Producto::factory()->create();
        $this->actingAs($this->admin)->post(route('produccion.guardar-receta'), [
            'id_producto' => $p->id, 'rendimiento' => 30,
            'ingredientes' => [
                ['id_materia' => $this->harina->id, 'cantidad' => 750, 'unidad' => 'sub'],   // 750 g
                ['id_materia' => $this->leche->id,  'cantidad' => 250, 'unidad' => 'sub'],   // 250 mL
            ],
        ])->assertRedirect(route('produccion.recetas'));

        $d = $p->fresh()->receta->detalles->keyBy('id_materia');
        $this->assertSame(0.75, $d[$this->harina->id]->cantidad);
        $this->assertSame(0.25, $d[$this->leche->id]->cantidad);
    }

    public function test_no_acepta_insumos_repetidos(): void
    {
        $p = Producto::factory()->create();
        $this->actingAs($this->admin)->post(route('produccion.guardar-receta'), [
            'id_producto' => $p->id, 'rendimiento' => 10,
            'ingredientes' => [
                ['id_materia' => $this->harina->id, 'cantidad' => 1],
                ['id_materia' => $this->harina->id, 'cantidad' => 2],
            ],
        ])->assertSessionHasErrors('ingredientes.1.id_materia');
        $this->assertNull($p->fresh()->receta);
    }

    public function test_recetas_muestra_costo_ganancia_y_avisos(): void
    {
        $this->pan();   // costo 0.05 c/u, se vende a 0.30 → ganas 83.3%
        $this->harina->update(['costo_unitario' => 0]);
        $r = $this->actingAs($this->admin)->get(route('produccion.recetas'))->assertOk();
        $r->assertSee('"Harina" no tiene costo');

        $this->harina->update(['costo_unitario' => 3]);
        $this->actingAs($this->admin)->get(route('produccion.recetas'))->assertOk()
            ->assertSee('Ganas 83.3%')->assertSee('S/ 0.05');
        $this->actingAs($this->admin)->get(route('produccion.recetas', ['estado' => 'sin', 'buscar' => 'zzz']))->assertOk()
            ->assertSee('Ningún producto coincide');
    }

    public function test_pantalla_de_editar_receta_y_detalle_de_produccion(): void
    {
        $p = $this->pan();
        $this->actingAs($this->admin)->get(route('produccion.receta.editar', $p))->assertOk()
            ->assertSee('Tanda y costos')->assertSee('Guardar receta');

        $this->actingAs($this->admin)->post(route('produccion.store'), ['id_producto' => $p->id, 'cantidad' => 100, 'fecha' => now()->toDateString()]);
        $prod = \App\Models\Produccion::first();
        $this->actingAs($this->admin)->get(route('produccion.show', $prod), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertSee('Insumos descontados')->assertSee('−1 kg', false)->assertSee('−500 mL', false)
            ->assertSee('S/ 5.00')->assertSee('Repetir producción');
    }
}
