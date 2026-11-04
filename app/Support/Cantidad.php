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

    /**
     * Cantidad con su unidad, pasando a la unidad más cómoda de leer:
     *   0.5 L → "500 mL"   ·   0.25 kg → "250 g"   ·   1500 mL → "1.5 L"   ·   1.5 L → "1.5 L"
     * Solo cambia cómo se MUESTRA; en la base de datos todo sigue en la unidad del insumo.
     */
    public static function conUnidad($valor, ?string $unidad, bool $conSigno = false): string
    {
        [$v, $u] = self::convertir((float) $valor, (string) $unidad);
        return trim(self::fmt($v, 3, $conSigno) . ' ' . $u);
    }

    /** @return array{0: float, 1: string} */
    public static function convertir(float $v, string $unidad): array
    {
        $abs = abs($v);
        $u = strtolower(trim($unidad));
        if ($abs > 0 && $abs < 1) {
            if (in_array($u, ['l', 'lt', 'lts', 'litro', 'litros'], true)) return [$v * 1000, 'mL'];
            if (in_array($u, ['kg', 'kilo', 'kilos', 'kilogramo', 'kilogramos'], true)) return [$v * 1000, 'g'];
        }
        if ($abs >= 1000) {
            if (in_array($u, ['ml', 'mililitro', 'mililitros'], true)) return [$v / 1000, 'L'];
            if (in_array($u, ['g', 'gr', 'gramo', 'gramos'], true)) return [$v / 1000, 'kg'];
        }
        return [$v, $unidad];
    }
}
