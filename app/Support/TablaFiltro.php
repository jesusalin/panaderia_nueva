<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Orden y filtros de las tablas interactivas (ver partials/tabla-interactiva) del lado del servidor,
 * para que el PDF y el Excel salgan EXACTAMENTE como el usuario ve la tabla en pantalla.
 *
 * La tabla agrega a los enlaces de PDF/Excel estos parámetros:
 *   t_ord = columna (clave)   t_dir = asc|desc   t_q = texto buscado   t_f[clave] = valor elegido
 *
 * $columnas: clave => ['titulo' => 'Fecha', 'valor' => fn($fila) => ..., 'texto' => fn($fila) => ...]
 *   - valor: con qué se ordena (número, fecha o texto)
 *   - texto: lo que se ve en la celda (para los filtros y la búsqueda); si falta, se usa valor
 */
class TablaFiltro
{
    public static function aplicar(Collection $filas, Request $request, array $columnas): Collection
    {
        $q = self::normal($request->input('t_q', ''));
        if ($q !== '') {
            $filas = $filas->filter(function ($f) use ($columnas, $q) {
                $todo = collect($columnas)->map(fn ($c) => self::texto($c, $f))->implode(' ');
                return str_contains(self::normal($todo), $q);
            });
        }

        foreach ((array) $request->input('t_f', []) as $clave => $valor) {
            if (!isset($columnas[$clave]) || !is_string($valor) || $valor === '') continue;
            $buscado = self::normal($valor);
            $filas = $filas->filter(fn ($f) => self::normal(self::texto($columnas[$clave], $f)) === $buscado);
        }

        $ord = $request->input('t_ord');
        if (is_string($ord) && isset($columnas[$ord])) {
            $desc = $request->input('t_dir') === 'desc';
            $valor = $columnas[$ord]['valor'];
            $filas = $filas->sort(function ($a, $b) use ($valor, $desc) {
                $x = $valor($a); $y = $valor($b);
                $r = (is_numeric($x) && is_numeric($y)) ? ($x <=> $y)
                    : strcmp(self::normal((string) $x), self::normal((string) $y));
                return $desc ? -$r : $r;
            });
        }

        return $filas->values();
    }

    /** Frase para el PDF/Excel: "Ordenado por Fecha (de mayor a menor) · Tipo: Entrada · Búsqueda: «harina»". */
    public static function descripcion(Request $request, array $columnas): ?string
    {
        $partes = [];
        $ord = $request->input('t_ord');
        if (is_string($ord) && isset($columnas[$ord])) {
            $partes[] = 'Ordenado por ' . $columnas[$ord]['titulo'] . ($request->input('t_dir') === 'desc' ? ' (de mayor a menor / Z-A)' : ' (de menor a mayor / A-Z)');
        }
        foreach ((array) $request->input('t_f', []) as $clave => $valor) {
            if (isset($columnas[$clave]) && is_string($valor) && $valor !== '') {
                $partes[] = $columnas[$clave]['titulo'] . ': ' . $valor;
            }
        }
        if (trim((string) $request->input('t_q', '')) !== '') {
            $partes[] = 'Búsqueda: «' . trim($request->input('t_q')) . '»';
        }
        return $partes ? implode(' · ', $partes) : null;
    }

    /** Los parámetros de la tabla que hay que conservar en los enlaces. */
    public static function parametros(Request $request): array
    {
        return array_filter($request->only(['t_ord', 't_dir', 't_q', 't_f']));
    }

    private static function texto(array $columna, $fila): string
    {
        $fn = $columna['texto'] ?? $columna['valor'];
        return (string) $fn($fila);
    }

    public static function normal(?string $s): string
    {
        $s = mb_strtolower(trim((string) $s));
        return strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}
