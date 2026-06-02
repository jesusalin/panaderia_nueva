<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Receta extends Model {
    protected $table = 'recetas';
    protected $fillable = ['id_producto','rendimiento','descripcion'];
    public function producto()  { return $this->belongsTo(Producto::class, 'id_producto'); }
    public function detalles()  { return $this->hasMany(RecetaDetalle::class, 'id_receta'); }
}
