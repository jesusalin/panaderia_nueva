<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model {
    protected $table = 'unidades_medida';
    public $timestamps = false;
    protected $fillable = ['nombre','abreviatura'];
    public function materiasPrimas() { return $this->hasMany(MateriaPrima::class, 'id_unidad'); }
}
