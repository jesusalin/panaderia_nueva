<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RecetaDetalle extends Model {
    protected $table = 'receta_detalles';
    protected $fillable = ['id_receta','id_materia','cantidad'];
    public function receta()  { return $this->belongsTo(Receta::class, 'id_receta'); }
    public function materia() { return $this->belongsTo(MateriaPrima::class, 'id_materia'); }
}
