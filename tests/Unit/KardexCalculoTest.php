<?php

namespace Tests\Unit;

use App\Support\KardexCalculo;
use PHPUnit\Framework\TestCase;

/**
 * El kardex es el reporte con el que el jurado va a revisar si el inventario
 * "cuadra". Se prueba aislado, sin base de datos: que cada movimiento (incluidos
 * ajustes y conteos físicos) cuente como entrada o salida, y que
 * saldo inicial + entradas − salidas = saldo final.
 */
class KardexCalculoTest extends TestCase
{
    private function fila(?float $antes, float $despues, string $tipo = 'entrada', float $cantidad = 0, ?string $motivo = 'compra'): array
    {
        $n = KardexCalculo::normalizar($antes, $despues, $tipo, $cantidad, $motivo);
        return ['antes' => $antes, 'despues' => $despues, 'entrada' => $n['entrada'], 'salida' => $n['salida']];
    }

    public function test_una_compra_es_entrada(): void
    {
        $n = KardexCalculo::normalizar(8, 18, 'entrada', 10, 'compra');
        $this->assertSame(10.0, $n['entrada']);
        $this->assertSame(0.0, $n['salida']);
        $this->assertFalse($n['es_ajuste']);
    }

    public function test_una_merma_es_salida(): void
    {
        $n = KardexCalculo::normalizar(18, 15, 'salida', 3, 'merma');
        $this->assertSame(0.0, $n['entrada']);
        $this->assertSame(3.0, $n['salida']);
    }

    public function test_un_ajuste_a_la_baja_cuenta_como_salida(): void
    {
        // Antes del arreglo, los ajustes salían en blanco y no sumaban en los totales.
        $n = KardexCalculo::normalizar(15, 13, 'ajuste', 2, 'ajuste_manual');
        $this->assertSame(0.0, $n['entrada']);
        $this->assertSame(2.0, $n['salida']);
        $this->assertTrue($n['es_ajuste']);
    }

    public function test_un_ajuste_al_alza_cuenta_como_entrada(): void
    {
        $n = KardexCalculo::normalizar(10, 12.5, 'ajuste', 2.5, 'ajuste_manual');
        $this->assertSame(2.5, $n['entrada']);
        $this->assertSame(0.0, $n['salida']);
    }

    public function test_registros_antiguos_sin_stock_antes_se_infieren_por_tipo(): void
    {
        $this->assertSame(4.0, KardexCalculo::normalizar(null, 14, 'entrada', 4, 'compra')['entrada']);
        $this->assertSame(4.0, KardexCalculo::normalizar(null, 6, 'salida', 4, 'venta')['salida']);
    }

    public function test_el_kardex_cuadra_incluyendo_ajustes(): void
    {
        // Saldo inicial 8: compra +10, merma −3, ajuste (conteo) −2, producción −1.5
        $filas = [
            $this->fila(8, 18, 'entrada', 10, 'compra'),
            $this->fila(18, 15, 'salida', 3, 'merma'),
            $this->fila(15, 13, 'ajuste', 2, 'ajuste_manual'),
            $this->fila(13, 11.5, 'salida', 1.5, 'produccion'),
        ];

        $r = KardexCalculo::resumir($filas, 11.5);

        $this->assertSame(8.0, $r['saldoInicial']);
        $this->assertSame(10.0, $r['totalEntradas']);
        $this->assertSame(6.5, $r['totalSalidas']);
        $this->assertSame(11.5, $r['saldoFinal']);
        $this->assertTrue($r['cuadra']);
    }

    public function test_detecta_cuando_el_kardex_no_cuadra(): void
    {
        // Hueco: el segundo movimiento "arranca" en 20 aunque el anterior terminó en 18.
        $filas = [
            $this->fila(8, 18, 'entrada', 10, 'compra'),
            $this->fila(20, 17, 'salida', 3, 'merma'),
        ];

        $this->assertFalse(KardexCalculo::resumir($filas, 17)['cuadra']);
    }

    public function test_sin_movimientos_el_saldo_no_se_mueve(): void
    {
        $r = KardexCalculo::resumir([], 25.0);
        $this->assertSame(25.0, $r['saldoInicial']);
        $this->assertSame(25.0, $r['saldoFinal']);
        $this->assertSame(0.0, $r['totalEntradas']);
        $this->assertTrue($r['cuadra']);
    }
}
