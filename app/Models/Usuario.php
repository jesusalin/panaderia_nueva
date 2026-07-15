<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable {
    use Notifiable;
    
    protected $table    = 'usuarios';
    protected $fillable = ['nombre','usuario','email','password','foto_perfil','id_rol','estado'];
    protected $hidden   = ['password'];
    protected $casts    = ['password' => 'hashed'];

    public function rol()         { return $this->belongsTo(Rol::class, 'id_rol'); }
    public function ventas()      { return $this->hasMany(Venta::class, 'id_usuario'); }
    public function compras()     { return $this->hasMany(Compra::class, 'id_usuario'); }
    public function movimientos() { return $this->hasMany(MovimientoInventario::class, 'id_usuario'); }

    /**
     * Verifica si el usuario tiene uno de los roles indicados.
     * Uso: $user->hasRole('admin') o $user->hasRole(['admin', 'almacenero'])
     */
    public function hasRole($roles): bool
    {
        $nombreRol = $this->rol?->nombre;
        if (!$nombreRol) return false;

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($nombreRol, $roles);
    }
}
