<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model {
    protected $table = 'ventas';
    protected $fillable = ['id_usuario','id_cliente','numero_venta','fecha_venta','subtotal','igv','total','tipo_pago','estado','observaciones'];
    protected $casts = ['fecha_venta' => 'datetime'];
    public function usuario()  { return $this->belongsTo(Usuario::class, 'id_usuario'); }
    public function cliente()  { return $this->belongsTo(Cliente::class, 'id_cliente'); }
    public function detalles() { return $this->hasMany(VentaDetalle::class, 'id_venta'); }
}
