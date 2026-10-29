<?php

namespace App\Support;

/**
 * Muestra cantidades sin ceros de sobra:
 *   25.000 → "25"   ·   0.500 → "0.5"   ·   1250.75 → "1,250.75"   ·   0.125 → "0.125"
 * (los precios en soles se siguen mostrando con 2 decimales, eso no cambia).
 */
class Cantidad
{
    public static function fmt($valor, int $decimales = 3, bool $conSigno = false): string
    {
        $n = round((float) $valor, $decimales);
        if (abs($n) < 0.5 / (10 ** $decimales)) $n = 0.0;   // evita "-0"

        $txt = number_format($n, $decimales, '.', ',');
        if (str_contains($txt, '.')) $txt = rtrim(rtrim($txt, '0'), '.');

        return ($conSigno && $n > 0 ? '+' : '') . $txt;
    }
}
