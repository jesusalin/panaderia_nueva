<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model {
    protected $table    = 'clientes';
    protected $fillable = [
        'nombre', 'tipo', 'ruc', 'dni', 'telefono', 
        'email', 'direccion', 'distrito', 'referencia', 'estado'
    ];

    public function ventas() { 
        return $this->hasMany(Venta::class, 'id_cliente'); 
    }

    public function totalComprado() {
        return $this->ventas()->where('estado', 'completada')->sum('total');
    }
}
