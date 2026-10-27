<?php

namespace App\Support;

/**
 * Cálculos del kardex, sin dependencias de Laravel ni de la base de datos,
 * para poder probarlos de forma aislada (ver tests/Unit/KardexCalculoTest.php).
 *
 * Regla central: la entrada o salida de un movimiento se deduce de la
 * VARIACIÓN REAL del stock (stock_después − stock_antes), no del tipo.
 * Así los ajustes de inventario y los conteos físicos (que se guardan con
 * tipo "ajuste" y la cantidad en valor absoluto) también cuentan como
 * entrada o salida y entran en los totales.
 */
class KardexCalculo
{
    /**
     * @return array{entrada: float, salida: float, es_ajuste: bool}
     */
    public static function normalizar($stockAntes, $stockDespues, ?string $tipo, $cantidad, ?string $motivo): array
    {
        if ($stockAntes !== null) {
            $delta = (float) $stockDespues - (float) $stockAntes;
        } else {
            // Registros antiguos sin stock_antes: se infiere por el tipo.
            $delta = $tipo === 'entrada' ? (float) $cantidad : -(float) $cantidad;
        }

        return [
            'entrada'   => $delta > 0 ? $delta : 0.0,
            'salida'    => $delta < 0 ? -$delta : 0.0,
            'es_ajuste' => $tipo === 'ajuste' || $motivo === 'ajuste_manual',
        ];
    }

    /**
     * Resume un periodo. Cada fila: ['antes' => ?float, 'despues' => float,
     * 'entrada' => float, 'salida' => float], en orden cronológico.
     *
     * @return array{totalEntradas: float, totalSalidas: float, saldoInicial: float, saldoFinal: float, cuadra: bool}
     */
    public static function resumir(array $filas, float $stockActual): array
    {
        $totalEntradas = 0.0;
        $totalSalidas  = 0.0;
        foreach ($filas as $f) {
            $totalEntradas += $f['entrada'];
            $totalSalidas  += $f['salida'];
        }

        if ($filas === []) {
            // Sin movimientos en el periodo: el saldo no se movió.
            return [
                'totalEntradas' => 0.0, 'totalSalidas' => 0.0,
                'saldoInicial'  => $stockActual, 'saldoFinal' => $stockActual, 'cuadra' => true,
            ];
        }

        $primero = $filas[0];
        $ultimo  = $filas[count($filas) - 1];

        $saldoInicial = $primero['antes'] !== null
            ? (float) $primero['antes']
            : (float) $primero['despues'] - $primero['entrada'] + $primero['salida'];
        $saldoFinal = (float) $ultimo['despues'];

        return [
            'totalEntradas' => $totalEntradas,
            'totalSalidas'  => $totalSalidas,
            'saldoInicial'  => $saldoInicial,
            'saldoFinal'    => $saldoFinal,
            // El kardex "cuadra" si: saldo inicial + entradas − salidas = saldo final
            'cuadra'        => abs(($saldoInicial + $totalEntradas - $totalSalidas) - $saldoFinal) < 0.01,
        ];
    }
}
