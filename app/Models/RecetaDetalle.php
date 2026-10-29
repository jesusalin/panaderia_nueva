<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RecetaDetalle extends Model {
    protected $table = 'receta_detalles';
    protected $fillable = ['id_receta','id_materia','cantidad'];
    // Cantidades como número (sin ceros de sobra al mostrarlas: 25.000 → 25)
    protected $casts = ['cantidad' => 'float'];
    public function receta()  { return $this->belongsTo(Receta::class, 'id_receta'); }
    public function materia() { return $this->belongsTo(MateriaPrima::class, 'id_materia'); }
}
