<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\KardexProducto;
use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\RecetaDetalle;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductosMejorasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create();
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'id_categoria' => Categoria::factory()->create()->id, 'nombre' => 'Pan de yema',
            'precio_venta' => 0.30, 'costo_produccion' => 0.20, 'stock_actual' => 40, 'stock_minimo' => 10, 'estado' => 'activo',
        ], $extra);
    }

    public function test_el_stock_inicial_queda_en_el_kardex(): void
    {
        $this->actingAs($this->admin)->post(route('productos.store'), $this->datos())->assertRedirect();
        $p = Producto::where('nombre', 'Pan de yema')->first();
        $this->assertSame(40, (int) $p->stock_actual);
        $this->assertDatabaseHas('kardex_productos', ['id_producto' => $p->id, 'tipo' => 'entrada', 'cantidad' => 40, 'stock_antes' => 0, 'stock_despues' => 40]);
    }

    public function test_editar_no_cambia_el_stock(): void
    {
        $p = Producto::factory()->create(['stock_actual' => 50]);
        $this->actingAs($this->admin)->put(route('productos.update', $p), $this->datos(['id_categoria' => $p->id_categoria, 'stock_actual' => 999]))
            ->assertRedirect();
        $this->assertSame(50, (int) $p->fresh()->stock_actual);
    }

    public function test_merma_y_conteo_ajustan_el_stock_y_dejan_rastro(): void
    {
        $p = Producto::factory()->create(['stock_actual' => 50]);

        $this->actingAs($this->admin)->post(route('productos.ajuste.store', $p), ['tipo_ajuste' => 'merma', 'cantidad' => 6, 'observacion' => 'Se quemó una bandeja'])
            ->assertRedirect(route('productos.show', $p));
        $this->assertSame(44, (int) $p->fresh()->stock_actual);
        $this->assertDatabaseHas('kardex_productos', ['id_producto' => $p->id, 'tipo' => 'salida', 'motivo' => 'ajuste_manual', 'cantidad' => 6, 'stock_antes' => 50, 'stock_despues' => 44]);

        $this->actingAs($this->admin)->post(route('productos.ajuste.store', $p), ['tipo_ajuste' => 'conteo', 'stock_real' => 47, 'observacion' => 'Conteo de la tarde']);
        $this->assertSame(47, (int) $p->fresh()->stock_actual);
        $this->assertDatabaseHas('kardex_productos', ['id_producto' => $p->id, 'tipo' => 'entrada', 'cantidad' => 3, 'stock_despues' => 47]);
        $this->assertDatabaseHas('actividad_usuarios', ['descripcion' => 'Ajustó el stock de "' . $p->nombre . '" (merma de 6 uds)']);
    }

    public function test_no_deja_stock_negativo_ni_ajustes_sin_motivo(): void
    {
        $p = Producto::factory()->create(['stock_actual' => 5]);
        $this->actingAs($this->admin)->post(route('productos.ajuste.store', $p), ['tipo_ajuste' => 'merma', 'cantidad' => 9, 'observacion' => 'x'])
            ->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('productos.ajuste.store', $p), ['tipo_ajuste' => 'merma', 'cantidad' => 1])
            ->assertSessionHasErrors('observacion');
        $this->assertSame(5, (int) $p->fresh()->stock_actual);
        $this->assertSame(0, KardexProducto::count());
    }

    public function test_margen_y_costo_segun_receta(): void
    {
        $p = Producto::factory()->create(['precio_venta' => 0.50, 'costo_produccion' => 0.20]);
        $this->assertSame(60.0, $p->margen);
        $this->assertSame(0.30, $p->ganancia);

        $idUnidad = DB::table('unidades_medida')->insertGetId(['nombre' => 'kilogramo', 'abreviatura' => 'kg']);
        $harina = MateriaPrima::create(['nombre' => 'Harina', 'id_unidad' => $idUnidad, 'stock_actual' => 10, 'stock_minimo' => 1, 'costo_unitario' => 3.00, 'estado' => 'activo']);
        $receta = Receta::create(['id_producto' => $p->id, 'rendimiento' => 30]);
        RecetaDetalle::create(['id_receta' => $receta->id, 'id_materia' => $harina->id, 'cantidad' => 2]);
        $this->assertSame(0.20, $p->fresh()->costoSegunReceta());   // 2 kg × 3.00 ÷ 30
    }

    public function test_listado_filtra_y_la_ficha_se_ve(): void
    {
        $bajo = Producto::factory()->create(['nombre' => 'Torta chica', 'stock_actual' => 2, 'stock_minimo' => 5]);
        Producto::factory()->create(['nombre' => 'Pan grande', 'stock_actual' => 80, 'stock_minimo' => 5]);

        $this->actingAs($this->admin)->get(route('productos.index', ['stock' => 'bajo']))->assertOk()
            ->assertSee('Torta chica')->assertDontSee('Pan grande');
        $this->actingAs($this->admin)->get(route('productos.index', ['orden' => 'vendidos']))->assertOk();
        $this->actingAs($this->admin)->get(route('productos.show', $bajo))->assertOk()->assertSee('Últimos movimientos');
        $this->actingAs($this->admin)->get(route('productos.ajuste', $bajo))->assertOk();
        $this->actingAs($this->admin)->get(route('productos.edit', $bajo))->assertOk()->assertSee('Ajustar');
    }
}
