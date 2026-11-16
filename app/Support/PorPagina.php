<?php

namespace App\Support;

/**
 * Cuántos registros mostrar por página en los listados ("Ver: 12 · 24 · 48 · Todos").
 * La elección se recuerda por pantalla durante la sesión, para no tener que elegirla cada vez.
 */
class PorPagina
{
    /** Tope de "Todos" para que una lista enorme no cuelgue el navegador. */
    public const TODOS = 1000;

    public static function opciones(int $defecto): array
    {
        return [$defecto, $defecto * 2, $defecto * 4];
    }

    public static function valor(int $defecto): int
    {
        $clave = 'por_pagina.' . (request()->route()?->getName() ?? request()->path());
        $pedido = request('por');
        if ($pedido !== null) {
            $valido = $pedido === 'todos' || in_array((int) $pedido, self::opciones($defecto), true);
            if ($valido) session([$clave => $pedido === 'todos' ? 'todos' : (int) $pedido]);
        }
        $elegido = session($clave, $defecto);
        if ($elegido !== 'todos' && !in_array((int) $elegido, self::opciones($defecto), true)) $elegido = $defecto;
        return $elegido === 'todos' ? self::TODOS : (int) $elegido;
    }
}
