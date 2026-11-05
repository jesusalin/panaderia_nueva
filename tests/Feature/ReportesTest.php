<?php

namespace Tests\Feature;

use App\Models\CierreInventario;
use App\Models\Compra;
use App\Models\KardexProducto;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\PermisoUsuario;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Support\CierreDiario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private MateriaPrima $harina;
    private MateriaPrima $huevo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create();
        $kg  = DB::table('unidades_medida')->insertGetId(['nombre' => 'kilogramo', 'abreviatura' => 'kg']);
        $und = DB::table('unidades_medida')->insertGetId(['nombre' => 'unidad', 'abreviatura' => 'und']);
        $this->harina = MateriaPrima::create(['nombre' => 'Harina', 'id_unidad' => $kg, 'stock_actual' => 30, 'stock_minimo' => 20, 'costo_unitario' => 3, 'estado' => 'activo']);
        $this->huevo  = MateriaPrima::create(['nombre' => 'Huevo', 'id_unidad' => $und, 'stock_actual' => 100, 'stock_minimo' => 30, 'costo_unitario' => 0.5, 'estado' => 'activo']);
    }

    private function mov(MateriaPrima $m, string $tipo, string $motivo, float $antes, float $despues, string $cuando, string $obs = ''): void
    {
        MovimientoInventario::create(['id_materia' => $m->id, 'id_usuario' => $this->admin->id, 'tipo' => $tipo, 'motivo' => $motivo,
            'cantidad' => abs($despues - $antes), 'stock_antes' => $antes, 'stock_despues' => $despues, 'observacion' => $obs, 'created_at' => $cuando]);
    }

    public function test_cierre_diario_calcula_saldos_del_dia(): void
    {
        $ayer = today()->subDay();
        // ayer: entra 50 (de 10 a 60) y salen 20 (queda 40)
        $this->mov($this->harina, 'entrada', 'compra', 10, 60, $ayer->copy()->setTime(9, 0));
        $this->mov($this->harina, 'salida', 'produccion', 60, 40, $ayer->copy()->setTime(15, 0));
        // hoy: sale 5, conteo corrige -5 (queda 30)
        $this->mov($this->harina, 'salida', 'produccion', 40, 35, today()->setTime(8, 0)->toDateTimeString());
        $this->mov($this->harina, 'ajuste', 'ajuste_manual', 35, 30, today()->setTime(9, 0)->toDateTimeString());

        $f = CierreDiario::calcular($ayer)->firstWhere('nombre', 'Harina');
        $this->assertEquals(10, $f['inicial']);
        $this->assertEquals(50, $f['entradas']);
        $this->assertEquals(20, $f['salidas']);
        $this->assertEquals(40, $f['final']);
        $this->assertTrue($f['cuadra']);

        $h = CierreDiario::calcular(today())->firstWhere('nombre', 'Harina');
        $this->assertEquals(40, $h['inicial']);
        $this->assertEquals(5, $h['salidas']);
        $this->assertEquals(-5, $h['ajuste']);
        $this->assertEquals(30, $h['final']);
        $this->assertEquals(90, $h['valor_final']);

        // Insumo sin movimientos: su saldo no cambia
        $hu = CierreDiario::calcular(today())->firstWhere('nombre', 'Huevo');
        $this->assertEquals(100, $hu['inicial']);
        $this->assertEquals(100, $hu['final']);
        $this->assertSame(0, $hu['movimientos']);
    }

    public function test_registrar_el_cierre_y_exportar(): void
    {
        $this->mov($this->harina, 'salida', 'produccion', 35, 30, now()->toDateTimeString());
        $this->actingAs($this->admin)->get(route('reportes.cierre'))->assertOk()->assertSee('Día sin cerrar')->assertSee('Harina');
        $this->actingAs($this->admin)->post(route('reportes.cierre.guardar'), ['fecha' => today()->toDateString(), 'observacion' => 'Todo bien'])
            ->assertRedirect(route('reportes.cierre', ['fecha' => today()->toDateString()]));
        $c = CierreInventario::firstOrFail();
        $this->assertSame(2, $c->insumos);
        $this->assertSame(1, $c->con_movimiento);
        $this->assertEquals(140, $c->valor_final);   // 30 × 3 + 100 × 0.5
        $this->assertCount(2, $c->detalle);

        // volver a cerrar no duplica
        $this->actingAs($this->admin)->post(route('reportes.cierre.guardar'), ['fecha' => today()->toDateString()]);
        $this->assertSame(1, CierreInventario::count());
        $this->actingAs($this->admin)->get(route('reportes.cierre'))->assertOk()->assertSee('Día cerrado')->assertSee('Volver a cerrar el día');

        // no se puede cerrar un día futuro
        $this->actingAs($this->admin)->post(route('reportes.cierre.guardar'), ['fecha' => today()->addDay()->toDateString()])->assertSessionHasErrors('fecha');

        $this->actingAs($this->admin)->get(route('reportes.cierre.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('reportes.cierre.excel'))->assertOk()->assertDownload('cierre-inventario-' . today()->format('Y-m-d') . '.xlsx');
        $this->actingAs($this->admin)->get(route('reportes.cierre', ['ver' => 'movimiento', 'buscar' => 'har']))->assertOk()->assertSee('Harina')->assertDontSee('>Huevo<', false);
    }

    public function test_reporte_de_mermas(): void
    {
        $this->mov($this->huevo, 'salida', 'merma', 100, 88, now()->toDateTimeString(), 'Merma: Se rompió o derramó — caja caída');
        $this->mov($this->harina, 'salida', 'merma', 30, 28, now()->toDateTimeString(), 'Harina vencida');
        $this->mov($this->harina, 'salida', 'produccion', 28, 20, now()->toDateTimeString());
        $cat = DB::table('categorias')->insertGetId(['nombre' => 'Panes', 'created_at' => now(), 'updated_at' => now()]);
        $pan = Producto::create(['id_categoria' => $cat, 'nombre' => 'Pan francés', 'precio_venta' => 0.3, 'costo_produccion' => 0.1, 'stock_actual' => 50, 'stock_minimo' => 10, 'estado' => 'activo']);
        KardexProducto::create(['id_producto' => $pan->id, 'id_usuario' => $this->admin->id, 'tipo' => 'salida', 'motivo' => 'ajuste_manual', 'cantidad' => 10,
            'stock_antes' => 60, 'stock_despues' => 50, 'observacion' => 'Merma: se quemaron', 'created_at' => now()]);

        $r = $this->actingAs($this->admin)->get(route('reportes.mermas'))->assertOk();
        // 12 huevos × 0.50 + 2 kg × 3 + 10 panes × 0.10 = 13.00
        $r->assertSee('S/ 13.00')->assertSee('Se rompió o derramó')->assertSee('Se venció')->assertSee('En mal estado');
        // índice: harina perdida 6 / (24 usada + 6) = 20 %; huevo 6 / (0 + 6)... → (6+6) / (24+12) = 33.3 %
        $r->assertSee('33.3 %');
        $this->actingAs($this->admin)->get(route('reportes.mermas', ['tipo' => 'productos', 'periodo' => 'todo']))->assertOk()->assertSee('Pan francés')->assertDontSee('Huevo');
        $this->actingAs($this->admin)->get(route('reportes.mermas.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('reportes.mermas.excel'))->assertOk();
    }

    public function test_reporte_de_compras(): void
    {
        $p = Proveedor::create(['nombre' => 'Molino', 'estado' => 'activo']);
        foreach ([[3.0, 10, 'recibida'], [3.3, 20, 'recibida'], [9.0, 5, 'anulada']] as $i => [$precio, $cant, $estado]) {
            $c = Compra::create(['id_proveedor' => $p->id, 'id_usuario' => $this->admin->id, 'fecha_compra' => today()->subDays(5 - $i), 'estado' => $estado]
                + Compra::totales($precio * $cant, false));
            $c->detalles()->create(['id_materia' => $this->harina->id, 'cantidad' => $cant, 'cantidad_recibida' => $cant, 'precio_unitario' => $precio, 'subtotal' => $precio * $cant]);
        }
        $r = $this->actingAs($this->admin)->get(route('reportes.compras', ['periodo' => '30']))->assertOk();
        // sin anuladas: 30 + 66 = 96 → con IGV 113.28; precio sube 3.00 → 3.30 (+10 %)
        $r->assertSee('S/ 113.28')->assertSee('▲ 10%')->assertSee('Molino');
        $this->actingAs($this->admin)->get(route('reportes.compras', ['desde' => today()->subDays(3)->toDateString()]))->assertOk();
        $this->actingAs($this->admin)->get(route('reportes.compras.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('reportes.compras.excel'))->assertOk();
    }

    public function test_resumen_stock_y_permisos(): void
    {
        $this->actingAs($this->admin)->get(route('reportes.index'))->assertOk()->assertSee('Cierre de hoy')->assertSee('Mermas del mes')->assertSee('Compras del mes');
        $this->actingAs($this->admin)->get(route('reportes.stock', ['orden' => 'dias']))->assertOk()->assertSee('Alcanza');
        $this->actingAs($this->admin)->get(route('reportes.stock.excel'))->assertOk();

        $u = Usuario::factory()->create();
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'reportes.stock']);
        $u = $u->fresh();
        $this->actingAs($u)->get(route('reportes.cierre'))->assertForbidden();
        $this->actingAs($u)->get(route('reportes.mermas'))->assertForbidden();
        PermisoUsuario::create(['id_usuario' => $u->id, 'modulo' => 'reportes.mermas']);
        $this->actingAs($u->fresh())->get(route('reportes.mermas'))->assertOk()->assertSee('data-menu-id="reportes-mermas"', false);
        // el admin ve los reportes nuevos en el menú
        $this->actingAs($this->admin)->get(route('reportes.index'))->assertSee('data-menu-id="reportes-mermas"', false)->assertSee('data-menu-id="reportes-compras"', false);
    }
}
