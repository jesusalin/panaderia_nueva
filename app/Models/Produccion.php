<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Produccion extends Model {
    protected $table = 'producciones';
    protected $fillable = ['id_producto','id_usuario','cantidad','fecha','observacion'];
    protected $casts = ['fecha' => 'date'];
    public function producto() { return $this->belongsTo(Producto::class, 'id_producto'); }
    public function usuario()  { return $this->belongsTo(Usuario::class, 'id_usuario'); }
}
