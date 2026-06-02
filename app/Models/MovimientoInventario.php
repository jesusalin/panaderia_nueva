<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model {
    protected $table = 'movimientos_inventario';
    public $timestamps = false;
    protected $fillable = ['id_materia','id_usuario','tipo','motivo','referencia_id','cantidad','stock_antes','stock_despues','observacion'];
    protected $casts = ['created_at' => 'datetime'];
    public function materia()  { return $this->belongsTo(MateriaPrima::class, 'id_materia'); }
    public function usuario()  { return $this->belongsTo(Usuario::class, 'id_usuario'); }
}
