<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MateriaPrima extends Model {
    protected $table = 'materia_prima';
    protected $fillable = ['nombre','id_unidad','id_proveedor','stock_actual','stock_minimo','cantidad_reposicion','costo_unitario','estado'];

    public function unidad()          { return $this->belongsTo(UnidadMedida::class, 'id_unidad'); }
    public function proveedor()       { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
    public function recetaDetalles()  { return $this->hasMany(RecetaDetalle::class, 'id_materia'); }
    public function compraDetalles()  { return $this->hasMany(CompraDetalle::class, 'id_materia'); }
    public function movimientos()     { return $this->hasMany(MovimientoInventario::class, 'id_materia'); }
    public function ordenesAutomaticas() { return $this->hasMany(OrdenAutomatica::class, 'id_materia'); }
    public function tieneStockBajo()  { return $this->stock_actual <= $this->stock_minimo; }
}
