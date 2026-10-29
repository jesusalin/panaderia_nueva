<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriasMejorasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guarda_el_icono_y_el_color_elegidos(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->post(route('categorias.store'), [
            'nombre' => 'Bebidas', 'estado' => 'activo', 'icono' => 'fa-mug-hot', 'color' => '#2980b9',
        ])->assertRedirect();

        $c = Categoria::where('nombre', 'Bebidas')->first();
        $this->assertSame('fa-mug-hot', $c->icono_final);
        $this->assertSame('#2980b9', $c->color_final);

        $this->actingAs($admin)->post(route('categorias.store'), [
            'nombre' => 'Otra', 'estado' => 'activo', 'icono' => 'fa-hackeo', 'color' => 'rojo',
        ])->assertSessionHasErrors(['icono', 'color']);
    }

    public function test_sin_icono_lo_adivina_por_el_nombre(): void
    {
        $this->assertSame('fa-birthday-cake', (new Categoria(['nombre' => 'Tortas']))->icono_final);
        $this->assertSame('fa-bread-slice', (new Categoria(['nombre' => 'Panes']))->icono_final);
        $this->assertSame(Categoria::COLOR_DEFECTO, (new Categoria(['nombre' => 'X']))->color_final);
    }

    public function test_la_ficha_lista_sus_productos(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $cat = Categoria::factory()->create(['nombre' => 'Panes']);
        Producto::factory()->create(['id_categoria' => $cat->id, 'nombre' => 'Pan francés']);
        Producto::factory()->create(['nombre' => 'Torta de chocolate']);

        $this->actingAs($admin)->get(route('categorias.show', $cat))->assertOk()
            ->assertSee('Pan francés')->assertDontSee('Torta de chocolate');
    }

    public function test_no_se_elimina_una_categoria_con_productos(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $cat   = Categoria::factory()->create();
        Producto::factory()->create(['id_categoria' => $cat->id]);

        $this->actingAs($admin)->delete(route('categorias.destroy', $cat))->assertSessionHas('error');
        $this->assertDatabaseHas('categorias', ['id' => $cat->id]);
    }

    public function test_buscar_y_contar_stock_bajo(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $panes = Categoria::factory()->create(['nombre' => 'Panes']);
        Categoria::factory()->create(['nombre' => 'Bebidas']);
        Producto::factory()->create(['id_categoria' => $panes->id, 'stock_actual' => 2, 'stock_minimo' => 10]);

        $this->actingAs($admin)->get(route('categorias.index', ['buscar' => 'pan']))->assertOk()
            ->assertSee('Panes')->assertDontSee('Bebidas')
            ->assertSee('1 con stock bajo');
    }
}
