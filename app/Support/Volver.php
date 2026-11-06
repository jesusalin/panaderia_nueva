<?php

namespace App\Support;

/**
 * Recuerda desde qué pantalla se abrió un formulario (editar, ajustar stock…)
 * para que "Cancelar" y "Guardar" regresen allí y no a otra pantalla.
 *
 * El formulario lleva un campo oculto "volver" con la URL de origen.
 */
class Volver
{
    /** URL a la que se debe volver, o $porDefecto si no hay una válida. */
    public static function url(string $porDefecto): string
    {
        $candidata = self::segura(request()->input('volver') ?: old('volver'));
        if (!$candidata && request()->isMethod('GET')) {
            // La pantalla desde la que se tocó el botón (con sus filtros). No se usa url()->previous():
            // la cambian las búsquedas y avisos que se cargan en segundo plano y mandaba a la ficha.
            $candidata = self::segura(request()->headers->get('referer'));
        }
        return $candidata ?? $porDefecto;
    }

    /** Solo se aceptan direcciones del mismo sistema y distintas a la pantalla actual. */
    private static function segura(?string $url): ?string
    {
        if (!$url) return null;
        $base = rtrim(url('/'), '/');
        if ($url !== $base && !str_starts_with($url, $base . '/') && !str_starts_with($url, $base . '?')) return null;
        $sinQuery = strtok($url, '?');
        if (request()->isMethod('GET') && $sinQuery === strtok(request()->url(), '?')) return null;   // la misma pantalla
        if (str_contains($sinQuery, '/login') || str_contains($sinQuery, '/logout')) return null;
        return $url;
    }
}
