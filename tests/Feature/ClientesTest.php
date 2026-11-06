<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Cliente $general;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create();
        $this->general = Cliente::factory()->create(['nombre' => Cliente::GENERAL]);
    }

    private function venta(Cliente $c, float $total, $cuando, string $estado = 'completada', ?Producto $p = null): Venta
    {
        $v = Venta::create(['id_usuario' => $this->admin->id, 'id_cliente' => $c->id, 'numero_venta' => 'V-' . uniqid(), 'fecha_venta' => $cuando,
            'tipo_pago' => 'efectivo', 'estado' => $estado] + Venta::totales($total));
        if ($p) $v->detalles()->create(['id_producto' => $p->id, 'cantidad' => (int) round($total / $p->precio_venta), 'precio_unitario' => $p->precio_venta, 'subtotal' => $total]);
        return $v;
    }

    public function test_registrar_valida_documentos_y_duplicados(): void
    {
        $datos = ['nombre' => 'Bodega  Doña Rosa', 'tipo' => 'bodega', 'ruc' => '20 123 456 789', 'telefono' => '987 654 321', 'contacto' => 'Sra. Rosa', 'distrito' => 'chorrillos', 'notas' => 'Pide 100 panes'];
        $r = $this->actingAs($this->admin)->post(route('clientes.store'), $datos);
        $c = Cliente::where('nombre', 'Bodega Doña Rosa')->firstOrFail();
        $r->assertRedirect(route('clientes.show', $c));
        $this->assertSame('20123456789', $c->ruc);
        $this->assertSame('Chorrillos', $c->distrito);
        $this->assertSame('51987654321', $c->whatsapp);
        $this->assertSame('activo', $c->estado);

        // Mismo nombre (sin importar mayúsculas), mismo RUC, RUC/DNI mal escritos
        $this->actingAs($this->admin)->post(route('clientes.store'), ['nombre' => 'bodega doña rosa', 'tipo' => 'bodega'])->assertSessionHasErrors('nombre');
        $this->actingAs($this->admin)->post(route('clientes.store'), ['nombre' => 'Otra', 'tipo' => 'bodega', 'ruc' => '20123456789'])->assertSessionHasErrors('ruc');
        $this->actingAs($this->admin)->post(route('clientes.store'), ['nombre' => 'Otra', 'tipo' => 'bodega', 'ruc' => '30123456789'])->assertSessionHasErrors('ruc');
        $this->actingAs($this->admin)->post(route('clientes.store'), ['nombre' => 'Otra', 'tipo' => 'particular', 'dni' => '1234'])->assertSessionHasErrors('dni');
        $this->actingAs($this->admin)->post(route('clientes.store'), ['nombre' => 'Otra', 'tipo' => 'nave'])->assertSessionHasErrors('tipo');

        // Al editar no choca consigo mismo
        $this->actingAs($this->admin)->put(route('clientes.update', $c), ['nombre' => 'Bodega Doña Rosa', 'tipo' => 'bodega', 'ruc' => '20123456789', 'estado' => 'activo'])
            ->assertSessionHasNoErrors()->assertRedirect(route('clientes.index'));
    }

    public function test_cliente_general_protegido_y_desactivar_o_eliminar(): void
    {
        $this->actingAs($this->admin)->put(route('clientes.update', $this->general), ['nombre' => 'Otro nombre', 'tipo' => 'particular', 'estado' => 'inactivo']);
        $this->assertSame(Cliente::GENERAL, $this->general->fresh()->nombre);
        $this->assertSame('activo', $this->general->fresh()->estado);
        $this->actingAs($this->admin)->put(route('clientes.toggle-estado', $this->general))->assertSessionHas('error');
        $this->actingAs($this->admin)->delete(route('clientes.destroy', $this->general))->assertSessionHas('error');

        $conVentas = Cliente::factory()->create(['nombre' => 'Colegio']);
        $this->venta($conVentas, 50, now());
        $this->actingAs($this->admin)->put(route('clientes.toggle-estado', $conVentas));
        $this->assertSame('inactivo', $conVentas->fresh()->estado);
        $this->actingAs($this->admin)->delete(route('clientes.destroy', $conVentas))->assertSessionHas('error');
        $this->assertNotNull($conVentas->fresh());
        $this->actingAs($this->admin)->put(route('clientes.toggle-estado', $conVentas));
        $this->assertSame('activo', $conVentas->fresh()->estado);

        $sinVentas = Cliente::factory()->create(['nombre' => 'Nunca compró']);
        $this->actingAs($this->admin)->delete(route('clientes.destroy', $sinVentas))->assertSessionHas('success');
        $this->assertNull($sinVentas->fresh());
    }

    public function test_listado_vistas_busqueda_y_ficha(): void
    {
        $cat = DB::table('categorias')->insertGetId(['nombre' => 'Panes', 'created_at' => now(), 'updated_at' => now()]);
        $pan = Producto::create(['id_categoria' => $cat, 'nombre' => 'Pan francés', 'precio_venta' => 0.2, 'costo_produccion' => 0.08, 'stock_actual' => 100, 'stock_minimo' => 1, 'estado' => 'activo']);
        $frecuente = Cliente::factory()->create(['nombre' => 'Bodega La Esquina', 'tipo' => 'bodega', 'telefono' => '998 877 665', 'dni' => '45678901']);
        foreach (range(1, 5) as $d) $this->venta($frecuente, 20, now()->subDays($d), 'completada', $pan);
        $this->venta($frecuente, 99, now(), 'anulada');
        $perdido = Cliente::factory()->create(['nombre' => 'Restaurante Lejano', 'tipo' => 'restaurante']);
        $this->venta($perdido, 80, now()->subDays(45));
        Cliente::factory()->create(['nombre' => 'Sin compras SAC']);

        $r = $this->actingAs($this->admin)->get(route('clientes.index'))->assertOk();
        $r->assertSee('Bodega La Esquina')->assertSee('frecuente (5 en 30 días)', false)->assertSee('dejaron de comprar');
        $this->actingAs($this->admin)->get(route('clientes.index', ['vista' => 'recuperar']))->assertOk()->assertSee('Restaurante Lejano')->assertDontSee('cl-nombre">Bodega La Esquina<', false);
        $this->actingAs($this->admin)->get(route('clientes.index', ['vista' => 'frecuentes']))->assertOk()->assertSee('Bodega La Esquina')->assertDontSee('cl-nombre">Restaurante Lejano<', false);
        $this->actingAs($this->admin)->get(route('clientes.index', ['vista' => 'sin_compras']))->assertOk()->assertSee('Sin compras SAC')->assertDontSee('cl-nombre">Restaurante Lejano<', false);
        // buscar por teléfono sin espacios y por DNI
        $this->actingAs($this->admin)->get(route('clientes.index', ['buscar' => '998877']))->assertOk()->assertSee('Bodega La Esquina')->assertDontSee('cl-nombre">Restaurante Lejano<', false);
        $this->actingAs($this->admin)->get(route('clientes.index', ['buscar' => '45678901']))->assertOk()->assertSee('Bodega La Esquina');
        // Estando en "Bodega", el botón de otro tipo cambia el filtro (antes se quedaba en bodega)
        $this->actingAs($this->admin)->get(route('clientes.index', ['tipo' => 'bodega']))->assertOk()
            ->assertSee('href="' . e(route('clientes.index', ['tipo' => 'restaurante'])) . '"', false);
        $this->actingAs($this->admin)->get(route('clientes.index', ['orden' => 'compras', 'tipo' => 'bodega']))->assertOk()->assertSee('Bodega La Esquina')->assertDontSee('cl-nombre">Restaurante Lejano<', false);

        // ficha: total sin anuladas, lo que más compra y WhatsApp
        $this->actingAs($this->admin)->get(route('clientes.show', $frecuente))->assertOk()
            ->assertSee('S/ 100.00')->assertSee('5 compras')->assertSee('Pan francés')->assertSee('wa.me/51998877665', false)->assertSee('anulada');
        $this->actingAs($this->admin)->get(route('clientes.show', $perdido))->assertOk()->assertSee('Hace 45 días que no compra');
        $this->actingAs($this->admin)->get(route('clientes.show', $frecuente), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('Abrir ficha')->assertDontSee('<html', false);
        $this->actingAs($this->admin)->get(route('clientes.create'))->assertOk()->assertSee('Registrar cliente');
        $this->actingAs($this->admin)->get(route('clientes.edit', $frecuente))->assertOk()->assertSee('Guardar cambios');
    }
}
