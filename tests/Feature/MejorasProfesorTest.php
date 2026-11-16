<?php

namespace Tests\Feature;

use App\Exports\KardexExport;
use App\Exports\TablaExport;
use App\Models\CierreInventario;
use App\Models\KardexProducto;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use App\Support\TablaFiltro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/** Mejoras pedidas por el profesor: stock, ver todos, cierre diario y tablas ordenables/filtrables. */
class MejorasProfesorTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private MateriaPrima $harina;
    private MateriaPrima $azucar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = Usuario::factory()->admin()->create(['nombre' => 'Ana']);
        $kg = DB::table('unidades_medida')->insertGetId(['nombre' => 'kilogramo', 'abreviatura' => 'kg']);
        $this->harina = MateriaPrima::create(['nombre' => 'Harina', 'id_unidad' => $kg, 'stock_actual' => 30, 'stock_minimo' => 20, 'costo_unitario' => 3, 'estado' => 'activo']);
        $this->azucar = MateriaPrima::create(['nombre' => 'Azúcar', 'id_unidad' => $kg, 'stock_actual' => 5, 'stock_minimo' => 10, 'costo_unitario' => 4, 'estado' => 'activo']);
        MateriaPrima::create(['nombre' => 'Zapallo', 'id_unidad' => $kg, 'stock_actual' => 8, 'stock_minimo' => 2, 'costo_unitario' => 1, 'estado' => 'activo']);
    }

    private function mov(MateriaPrima $m, string $tipo, string $motivo, float $antes, float $despues, string $cuando, string $obs = ''): void
    {
        MovimientoInventario::create(['id_materia' => $m->id, 'id_usuario' => $this->admin->id, 'tipo' => $tipo, 'motivo' => $motivo,
            'cantidad' => abs($despues - $antes), 'stock_antes' => $antes, 'stock_despues' => $despues, 'observacion' => $obs, 'created_at' => $cuando]);
    }

    // ── Reporte de stock: el orden se ve y el PDF/Excel salen igual ──

    public function test_stock_se_ordena_por_nombre_en_ambos_sentidos_y_lo_explica(): void
    {
        $r = $this->actingAs($this->admin)->get(route('reportes.stock', ['tipo' => 'materia', 'orden' => 'nombre']));
        $r->assertOk()->assertSee('El PDF y el Excel salen tal como ves la lista')->assertSee('Ordenado por nombre (A-Z)');
        $this->assertSame(['Azúcar', 'Harina', 'Zapallo'], $r->viewData('materias')->pluck('nombre')->all());

        $r = $this->actingAs($this->admin)->get(route('reportes.stock', ['tipo' => 'materia', 'orden' => 'nombre', 'dir' => 'desc']));
        $this->assertSame(['Zapallo', 'Harina', 'Azúcar'], $r->viewData('materias')->pluck('nombre')->all());
        $r->assertSee('Ordenado por nombre (Z-A)');

        // por defecto: los más urgentes primero
        $r = $this->actingAs($this->admin)->get(route('reportes.stock', ['tipo' => 'materia']));
        $this->assertSame('Azúcar', $r->viewData('materias')->first()->nombre);
    }

    public function test_el_excel_de_stock_sale_con_el_orden_elegido(): void
    {
        Excel::fake();
        $this->actingAs($this->admin)->get(route('reportes.stock.excel', ['tipo' => 'materia', 'orden' => 'nombre', 'dir' => 'desc']));
        Excel::assertDownloaded('reporte-stock-' . now()->format('Y-m-d') . '.xlsx', function ($export) {
            return $export->collection()->pluck('nombre')->all() === ['Zapallo', 'Harina', 'Azúcar'];
        });
    }

    // ── Ver todos por página ──

    public function test_productos_se_pueden_ver_todos_y_se_recuerda(): void
    {
        Producto::factory()->count(15)->create();
        $r = $this->actingAs($this->admin)->get(route('productos.index'));
        $this->assertSame(12, $r->viewData('productos')->perPage());
        $r->assertSee('Todos');

        $r = $this->actingAs($this->admin)->get(route('productos.index', ['por' => 'todos']));
        $this->assertCount(15, $r->viewData('productos')->items());

        // se recuerda al volver sin elegir
        $r = $this->actingAs($this->admin)->get(route('productos.index'));
        $this->assertCount(15, $r->viewData('productos')->items());

        // valores raros se ignoran
        $r = $this->actingAs($this->admin)->get(route('productos.index', ['por' => '7']));
        $this->assertCount(15, $r->viewData('productos')->items());
        $r = $this->actingAs($this->admin)->get(route('productos.index', ['por' => '24']));
        $this->assertSame(24, $r->viewData('productos')->perPage());
    }

    // ── Tablas: orden y filtros del lado del servidor ──

    public function test_tabla_filtro_ordena_filtra_y_describe(): void
    {
        $filas = collect([
            ['n' => 'Pan', 'tipo' => 'Entrada', 'q' => 5], ['n' => 'Ñoqui', 'tipo' => 'Salida', 'q' => 20],
            ['n' => 'Árbol', 'tipo' => 'Entrada', 'q' => 1],
        ]);
        $cols = ['n' => ['titulo' => 'Nombre', 'valor' => fn ($f) => $f['n']], 'tipo' => ['titulo' => 'Tipo', 'valor' => fn ($f) => $f['tipo']],
                 'q' => ['titulo' => 'Cantidad', 'valor' => fn ($f) => $f['q']]];

        $r = new Request(['t_ord' => 'n']);
        $this->assertSame(['Árbol', 'Ñoqui', 'Pan'], TablaFiltro::aplicar($filas, $r, $cols)->pluck('n')->all());
        $r = new Request(['t_ord' => 'q', 't_dir' => 'desc', 't_f' => ['tipo' => 'entrada']]);
        $this->assertSame(['Pan', 'Árbol'], TablaFiltro::aplicar($filas, $r, $cols)->pluck('n')->all());
        $this->assertSame('Ordenado por Cantidad (de mayor a menor / Z-A) · Tipo: entrada', TablaFiltro::descripcion($r, $cols));
        $r = new Request(['t_q' => 'noq']);
        $this->assertSame(['Ñoqui'], TablaFiltro::aplicar($filas, $r, $cols)->pluck('n')->all());
        $r = new Request(['t_ord' => 'columna_que_no_existe', 't_f' => ['otra' => 'x']]);
        $this->assertCount(3, TablaFiltro::aplicar($filas, $r, $cols));
    }

    public function test_kardex_marca_la_tabla_y_el_excel_sale_filtrado_y_ordenado(): void
    {
        $this->mov($this->harina, 'entrada', 'compra', 0, 50, now()->subDays(3)->toDateTimeString());
        $this->mov($this->harina, 'salida', 'produccion', 50, 40, now()->subDays(2)->toDateTimeString());
        $this->mov($this->harina, 'salida', 'produccion', 40, 30, now()->subDay()->toDateTimeString());

        $this->actingAs($this->admin)->get(route('reportes.kardex', ['item' => 'materia-' . $this->harina->id, 'periodo' => 'todo']))
            ->assertOk()->assertSee('data-tabla="kardex"', false)->assertSee('data-exportar=".js-exp-kardex"', false)
            ->assertSee('data-filtro', false)->assertSee('El PDF y el Excel salen igual');

        Excel::fake();
        $this->actingAs($this->admin)->get(route('reportes.kardex.excel', ['item' => 'materia-' . $this->harina->id, 'periodo' => 'todo',
            't_f' => ['tipo' => 'Salida'], 't_ord' => 'saldo', 't_dir' => 'asc']));
        Excel::assertDownloaded('kardex-harina.xlsx', function (KardexExport $export) {
            $filas = $export->array();
            $saldos = array_column(array_slice($filas, 3), 5);
            return str_contains($filas[0][0], 'Tipo: Salida') && $saldos === ['30 kg', '40 kg'];
        });

        $this->actingAs($this->admin)->get(route('reportes.kardex.pdf', ['item' => 'materia-' . $this->harina->id, 'periodo' => 'todo', 't_f' => ['tipo' => 'Salida']]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    // ── Cierre diario mejorado ──

    public function test_cierre_guarda_el_tiempo_de_cierre_y_los_productos(): void
    {
        $pan = Producto::factory()->create(['nombre' => 'Pan francés', 'stock_actual' => 80, 'costo_produccion' => 0.1]);
        KardexProducto::create(['id_producto' => $pan->id, 'id_usuario' => $this->admin->id, 'tipo' => 'entrada', 'motivo' => 'produccion',
            'cantidad' => 100, 'stock_antes' => 0, 'stock_despues' => 100, 'created_at' => now()->startOfDay()->addHours(6)]);
        KardexProducto::create(['id_producto' => $pan->id, 'id_usuario' => $this->admin->id, 'tipo' => 'salida', 'motivo' => 'venta',
            'cantidad' => 20, 'stock_antes' => 100, 'stock_despues' => 80, 'created_at' => now()->startOfDay()->addHours(9)]);
        $this->mov($this->harina, 'salida', 'merma', 32, 30, now()->startOfDay()->addHours(7)->toDateTimeString(), 'Merma: se mojó');

        $r = $this->actingAs($this->admin)->get(route('reportes.cierre', ['de' => 'productos']));
        $r->assertOk()->assertSee('Productos terminados')->assertSee('Pan francés')->assertSee('Lo que pasó en el día')
          ->assertSee('Tiempo de cierre')->assertSee('data-tabla="cierre"', false);
        $fila = $r->viewData('vista')->firstWhere('nombre', 'Pan francés');
        $this->assertEquals([0, 100, 20, 80], [$fila['inicial'], $fila['entradas'], $fila['salidas'], $fila['final']]);
        $this->assertEquals(6.0, $r->viewData('resumenDia')['merma']);   // 2 kg de harina a S/ 3

        $this->actingAs($this->admin)->post(route('reportes.cierre.guardar'), ['fecha' => today()->toDateString(), 'abierto' => now()->timestamp - 95])
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Tiempo de cierre: 1 min 35 s'));
        $c = CierreInventario::first();
        $this->assertSame(95, $c->segundos);
        $this->assertEquals(8.0, $c->valor_productos);
        $this->assertSame('Pan francés', $c->detalle_productos[0]['nombre']);

        // un tiempo imposible (más de 2 horas) no se guarda
        $this->actingAs($this->admin)->post(route('reportes.cierre.guardar'), ['fecha' => today()->toDateString(), 'abierto' => now()->timestamp - 9000]);
        $this->assertNull($c->fresh()->segundos);

        $this->actingAs($this->admin)->get(route('reportes.cierre'))->assertOk()->assertSee('Cerrado por');
        $this->actingAs($this->admin)->get(route('reportes.cierre.pdf', ['de' => 'productos']))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_el_excel_del_cierre_sale_con_el_orden_y_filtro_de_la_tabla(): void
    {
        Excel::fake();
        $this->actingAs($this->admin)->get(route('reportes.cierre.excel', ['t_ord' => 'valor', 't_dir' => 'desc', 't_f' => ['estado' => 'Normal']]));
        Excel::assertDownloaded('cierre-inventario-' . today()->format('Y-m-d') . '.xlsx', function (TablaExport $export) {
            $nombres = collect($export->array())->pluck(0)->filter()->values()->all();
            // Harina (S/ 90) y Zapallo (S/ 8) están normales; Azúcar está bajo el mínimo
            return $nombres === ['Harina', 'Zapallo', 'TOTAL (2)'];
        });
    }
}
