<?php

namespace Tests\Feature;

use App\Models\CierreInventario;
use App\Models\KardexProducto;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Los datos de ejemplo (DatabaseSeeder) cuadran y cada usuario tiene lo de su puesto. */
class DatosDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_datos_de_ejemplo_cuadran_y_los_usuarios_tienen_lo_de_su_puesto(): void
    {
        $this->seed();

        // El stock de cada insumo y producto es el último saldo de su kardex
        foreach (MateriaPrima::all() as $m) {
            $ultimo = MovimientoInventario::where('id_materia', $m->id)->orderByDesc('created_at')->orderByDesc('id')->first();
            if ($ultimo) $this->assertEqualsWithDelta((float) $ultimo->stock_despues, (float) $m->stock_actual, 0.001, $m->nombre);
        }
        foreach (Producto::all() as $p) {
            $ultimo = KardexProducto::where('id_producto', $p->id)->orderByDesc('created_at')->orderByDesc('id')->first();
            if ($ultimo) $this->assertEqualsWithDelta((float) $ultimo->stock_despues, (float) $p->stock_actual, 0.001, $p->nombre);
        }

        // El almacenero (usuario de la tesis) solo ve lo del inventario
        $alm = Usuario::where('usuario', 'almacenero')->first();
        $this->assertTrue($alm->hasPermiso('reportes.cierre'));
        $this->assertTrue($alm->hasPermiso('inventario.conteo'));
        foreach (['ventas', 'clientes', 'reportes.ventas', 'catalogo.productos'] as $clave) {
            $this->assertFalse($alm->hasPermiso($clave), $clave);
        }
        // La vendedora no edita productos ni anula ventas, pero sí registra mermas
        $maria = Usuario::where('usuario', 'mquispe')->first();
        $this->assertFalse($maria->puede('catalogo.productos.editar'));
        $this->assertFalse($maria->puede('ventas.anular'));
        $this->assertTrue($maria->puede('catalogo.productos.ajustar'));
        $this->assertTrue((bool) Usuario::where('usuario', 'kflores')->value('debe_cambiar_password'));

        // Cierres diarios con su tiempo (TCI) y ninguno quedó desactualizado
        $this->assertGreaterThan(5, CierreInventario::whereNotNull('segundos')->count());
        $ultimo = CierreInventario::orderByDesc('fecha')->first();
        $foto = collect($ultimo->detalle)->keyBy('id');
        foreach (\App\Support\CierreDiario::calcular($ultimo->fecha) as $f) {
            $this->assertEqualsWithDelta($foto[$f['id']]['final'], $f['final'], 0.0005, $f['nombre']);
            $this->assertTrue($f['cuadra'], $f['nombre']);
        }

        // Las pantallas principales abren con los datos de ejemplo
        $this->withoutVite();
        foreach (['dashboard', 'reportes.index', 'reportes.cierre', 'reportes.stock', 'reportes.mermas', 'compras.index'] as $ruta) {
            $this->actingAs($alm)->get(route($ruta))->assertOk();
        }
    }
}
