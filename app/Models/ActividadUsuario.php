<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Una línea del historial de actividad: quién hizo qué y cuándo.
 * Uso: ActividadUsuario::registrar('crear', 'Registró una compra', 'Compras');
 */
class ActividadUsuario extends Model
{
    protected $table = 'actividad_usuarios';
    public $timestamps = false;
    protected $fillable = ['id_usuario', 'usuario_texto', 'accion', 'modulo', 'descripcion', 'ip', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];

    /** Tipos de acción => [etiqueta, ícono, color] para mostrar en pantalla. */
    const ACCIONES = [
        'inicio_sesion' => ['Inicio de sesión', 'fa-sign-in-alt',  'info'],
        'cierre_sesion' => ['Cierre de sesión', 'fa-sign-out-alt', 'secondary'],
        'login_fallido' => ['Intento fallido',  'fa-user-lock',    'danger'],
        'crear'         => ['Registro',         'fa-plus',         'success'],
        'editar'        => ['Edición',          'fa-pen',          'warning'],
        'eliminar'      => ['Eliminación',      'fa-trash-alt',    'danger'],
        'otra'          => ['Otra acción',      'fa-bolt',         'primary'],
    ];

    public function usuario() { return $this->belongsTo(Usuario::class, 'id_usuario'); }

    /**
     * Registra una acción. Nunca interrumpe al usuario: si algo falla (por ejemplo,
     * falta correr la migración) solo se anota el error en el log.
     */
    public static function registrar(string $accion, string $descripcion, ?string $modulo = null, ?Usuario $usuario = null, ?string $usuarioTexto = null): void
    {
        try {
            $usuario ??= auth()->user();
            static::create([
                'id_usuario'    => $usuario?->id,
                'usuario_texto' => $usuarioTexto ?? $usuario?->usuario,
                'accion'        => $accion,
                'modulo'        => $modulo,
                'descripcion'   => mb_substr($descripcion, 0, 255),
                'ip'            => request()?->ip(),
                'created_at'    => now(),
            ]);
        } catch (QueryException $e) {
            report($e);
        }
    }
}
