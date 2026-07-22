<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba el flujo de negocio más crítico del sistema: una venta no es solo
 * un registro, es una operación que debe descontar stock de forma atómica
 * (o no descontarlo en absoluto si algo falla) y poder revertirse limpio.
 */
class VentaStockTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = Usuario::factory()->admin()->create();
    }

    public function test_crear_una_venta_descuenta_el_stock_del_producto(): void
    {
        $producto = Producto::factory()->create(['stock_actual' => 50]);
        $cliente  = Cliente::factory()->create();

        $response = $this->actingAs($this->usuario)->post(route('ventas.store'), [
            'id_cliente' => $cliente->id,
            'tipo_pago'  => 'efectivo',
            'productos'  => [
                ['id_producto' => $producto->id, 'cantidad' => 5],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame(45, $producto->fresh()->stock_actual);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('venta_detalles', 1);
    }

    public function test_no_se_puede_vender_mas_stock_del_que_hay_y_no_se_descuenta_nada(): void
    {
        $producto = Producto::factory()->create(['stock_actual' => 2]);
        $cliente  = Cliente::factory()->create();

        $response = $this->actingAs($this->usuario)->post(route('ventas.store'), [
            'id_cliente' => $cliente->id,
            'tipo_pago'  => 'efectivo',
            'productos'  => [
                ['id_producto' => $producto->id, 'cantidad' => 5],
            ],
        ]);

        // La venta se rechaza (stock insuficiente) y el stock queda intacto:
        // la transacción de store() debe revertirse por completo.
        $response->assertSessionHasErrors('error');
        $this->assertSame(2, $producto->fresh()->stock_actual);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_anular_una_venta_devuelve_el_stock_al_producto(): void
    {
        $producto = Producto::factory()->create(['stock_actual' => 50]);
        $cliente  = Cliente::factory()->create();

        $this->actingAs($this->usuario)->post(route('ventas.store'), [
            'id_cliente' => $cliente->id,
            'tipo_pago'  => 'efectivo',
            'productos'  => [
                ['id_producto' => $producto->id, 'cantidad' => 5],
            ],
        ]);

        $this->assertSame(45, $producto->fresh()->stock_actual);

        $venta = Venta::first();
        $response = $this->actingAs($this->usuario)->put(route('ventas.anular', $venta));

        $response->assertRedirect();
        $this->assertSame('anulada', $venta->fresh()->estado);
        $this->assertSame(50, $producto->fresh()->stock_actual);
    }

    public function test_no_se_puede_anular_dos_veces_la_misma_venta(): void
    {
        $producto = Producto::factory()->create(['stock_actual' => 50]);
        $cliente  = Cliente::factory()->create();

        $this->actingAs($this->usuario)->post(route('ventas.store'), [
            'id_cliente' => $cliente->id,
            'tipo_pago'  => 'efectivo',
            'productos'  => [
                ['id_producto' => $producto->id, 'cantidad' => 5],
            ],
        ]);

        $venta = Venta::first();
        $this->actingAs($this->usuario)->put(route('ventas.anular', $venta));

        // Segundo intento de anular: no debe volver a sumar stock.
        $response = $this->actingAs($this->usuario)->put(route('ventas.anular', $venta));

        $response->assertSessionHasErrors('error');
        $this->assertSame(50, $producto->fresh()->stock_actual);
    }
}
