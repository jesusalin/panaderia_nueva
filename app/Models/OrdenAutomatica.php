<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OrdenAutomatica extends Model {
    protected $table    = 'ordenes_automaticas';
    protected $fillable = ['id_materia','id_proveedor','stock_al_generar','stock_minimo','cantidad_sugerida','id_compra','estado'];

    public function materia()    { return $this->belongsTo(MateriaPrima::class, 'id_materia'); }
    public function proveedor()  { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
    public function compra()     { return $this->belongsTo(Compra::class, 'id_compra'); }
}
