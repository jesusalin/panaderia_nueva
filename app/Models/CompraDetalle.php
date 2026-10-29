<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CompraDetalle extends Model {
    protected $table = 'compra_detalles';
    protected $fillable = ['id_compra','id_materia','cantidad','precio_unitario','subtotal'];
    // Cantidades como número (sin ceros de sobra al mostrarlas: 25.000 → 25)
    protected $casts = ['cantidad' => 'float'];
    public function compra()  { return $this->belongsTo(Compra::class, 'id_compra'); }
    public function materia() { return $this->belongsTo(MateriaPrima::class, 'id_materia'); }
}
