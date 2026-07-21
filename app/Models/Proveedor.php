<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model {
    protected $table = 'proveedores';
    protected $fillable = ['nombre','ruc','direccion','telefono','email','contacto','estado'];
    public function compras() { return $this->hasMany(Compra::class, 'id_proveedor'); }
    public function ordenesAutomaticas() { return $this->hasMany(OrdenAutomatica::class, 'id_proveedor'); }
}
