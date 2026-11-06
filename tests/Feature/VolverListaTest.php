<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Editar: "Cancelar" y "Guardar" regresan a la lista desde donde se abrió, no a la ficha. */
class VolverListaTest extends TestCase
{
    use RefreshDatabase;

    public function test_editar_vuelve_a_la_lista_con_sus_filtros(): void
    {
        $this->withoutVite();
        $admin = Usuario::factory()->admin()->create();
        $prov = Proveedor::create(['nombre' => 'Molino', 'estado' => 'activo']);
        $cat  = Categoria::create(['nombre' => 'Panes']);
        $cli  = Cliente::factory()->create();

        foreach ([['proveedores', ['proveedor' => $prov]], ['categorias', ['categoria' => $cat]], ['clientes', ['cliente' => $cli]], ['usuarios', ['usuario' => $admin]]] as [$m, $param]) {
            $lista = route("$m.index", ['buscar' => 'x']);
            $this->actingAs($admin)->get(route("$m.edit", $param + ['volver' => $lista]))->assertOk()
                ->assertSee('name="volver" value="' . e($lista) . '"', false)->assertSee('href="' . e($lista) . '"', false);
            // sin saber de dónde vino: a la lista
            $this->actingAs($admin)->get(route("$m.edit", $param))->assertOk()->assertSee('name="volver" value="' . route("$m.index") . '"', false);
        }
        // Al guardar el proveedor vuelve a la lista (antes iba a su ficha)
        $this->actingAs($admin)->put(route('proveedores.update', $prov), ['nombre' => 'Molino SAC', 'estado' => 'activo', 'volver' => route('proveedores.index', ['buscar' => 'mol'])])
            ->assertRedirect(route('proveedores.index', ['buscar' => 'mol']));
        // Una dirección de otro sitio no se acepta
        $this->actingAs($admin)->put(route('categorias.update', $cat), ['nombre' => 'Panes', 'estado' => 'activo', 'volver' => 'https://otro-sitio.com'])
            ->assertRedirect(route('categorias.index'));
    }
}
