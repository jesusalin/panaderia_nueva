<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model {
    protected $table = 'productos';
    protected $fillable = ['id_categoria','nombre','descripcion','precio_venta','costo_produccion','stock_actual','stock_minimo','imagen','estado'];
    public function categoria()      { return $this->belongsTo(Categoria::class, 'id_categoria'); }
    public function receta()         { return $this->hasOne(Receta::class, 'id_producto'); }
    public function ventaDetalles()  { return $this->hasMany(VentaDetalle::class, 'id_producto'); }
    public function producciones()   { return $this->hasMany(Produccion::class, 'id_producto'); }
    public function kardex()         { return $this->hasMany(KardexProducto::class, 'id_producto'); }
    public function tieneStockBajo() { return $this->stock_actual <= $this->stock_minimo; }
}
