<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ajustes generales del sistema guardados como clave => valor (JSON).
 * Uso: Configuracion::obtener('orden_menu'); Configuracion::guardar('orden_menu', [...]);
 */
class Configuracion extends Model
{
    protected $table    = 'configuraciones';
    protected $fillable = ['clave', 'valor'];
    protected $casts    = ['valor' => 'array'];

    public static function obtener(string $clave, $porDefecto = null)
    {
        return static::where('clave', $clave)->first()?->valor ?? $porDefecto;
    }

    public static function guardar(string $clave, $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }

    public static function borrar(string $clave): void
    {
        static::where('clave', $clave)->delete();
    }
}
