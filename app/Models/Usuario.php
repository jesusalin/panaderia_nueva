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
}
