<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable {
    use Notifiable;

    protected $table    = 'usuarios';
    protected $fillable = ['nombre','apodo','usuario','email','password','foto_perfil','id_rol','estado'];
    protected $hidden   = ['password'];
    protected $casts    = ['password' => 'hashed'];

    /**
     * Lista de módulos que se pueden asignar a un usuario.
     * clave => etiqueta legible (usada en el formulario de usuarios y en las rutas/menú).
     */
    const MODULOS = [
        'catalogo'   => 'Catálogo (Categorías y Productos)',
        'inventario' => 'Inventario (Materia Prima, Movimientos, Kardex)',
        'produccion' => 'Producción y Recetas',
        'compras'    => 'Compras (Proveedores, Compras, Órdenes Automáticas)',
        'clientes'   => 'Clientes',
        'ventas'     => 'Ventas',
        'reportes'   => 'Reportes (Tiempos por Operación)',
    ];

    public function rol()         { return $this->belongsTo(Rol::class, 'id_rol'); }
    public function ventas()      { return $this->hasMany(Venta::class, 'id_usuario'); }
    public function compras()     { return $this->hasMany(Compra::class, 'id_usuario'); }
    public function movimientos() { return $this->hasMany(MovimientoInventario::class, 'id_usuario'); }
    public function permisos()    { return $this->hasMany(PermisoUsuario::class, 'id_usuario'); }

    /**
     * true si el usuario es administrador (acceso total, sin importar permisos_usuario).
     */
    public function isAdmin(): bool
    {
        return $this->rol?->nombre === 'admin';
    }

    /**
     * true si el usuario puede acceder al módulo indicado.
     * El admin siempre tiene acceso a todo. Un usuario normal necesita
     * tener el módulo asignado en la tabla permisos_usuario.
     * Uso: $user->hasModulo('inventario')
     */
    public function hasModulo(string $modulo): bool
    {
        if ($this->isAdmin()) return true;

        return $this->permisos->contains('modulo', $modulo);
    }

    /**
     * Devuelve las claves de los módulos asignados a este usuario (para
     * precargar los checkboxes en el formulario de edición).
     */
    public function modulosAsignados(): array
    {
        return $this->permisos->pluck('modulo')->all();
    }
}
