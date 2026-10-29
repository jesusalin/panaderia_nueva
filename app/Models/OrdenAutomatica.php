<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OrdenAutomatica extends Model {
    protected $table    = 'ordenes_automaticas';
    protected $fillable = ['id_materia','id_proveedor','stock_al_generar','stock_minimo','cantidad_sugerida','id_compra','estado'];
    // Cantidades como número (sin ceros de sobra al mostrarlas: 25.000 → 25)
    protected $casts = ['stock_al_generar' => 'float', 'stock_minimo' => 'float', 'cantidad_sugerida' => 'float'];

    public function materia()    { return $this->belongsTo(MateriaPrima::class, 'id_materia'); }
    public function proveedor()  { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
    public function compra()     { return $this->belongsTo(Compra::class, 'id_compra'); }
}
