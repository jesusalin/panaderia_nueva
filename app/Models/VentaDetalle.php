<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model {
    protected $table = 'venta_detalles';
    protected $fillable = ['id_venta','id_producto','cantidad','precio_unitario','subtotal'];
    public function venta()    { return $this->belongsTo(Venta::class, 'id_venta'); }
    public function producto() { return $this->belongsTo(Producto::class, 'id_producto'); }
}
