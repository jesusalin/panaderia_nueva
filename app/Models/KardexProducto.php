<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class KardexProducto extends Model {
    protected $table    = 'kardex_productos';
    public $timestamps  = false;
    protected $fillable = ['id_producto','id_usuario','tipo','motivo','referencia_id','cantidad','stock_antes','stock_despues','observacion'];
    protected $casts    = ['created_at' => 'datetime'];

    public function producto() { return $this->belongsTo(Producto::class, 'id_producto'); }
    public function usuario()  { return $this->belongsTo(Usuario::class, 'id_usuario'); }
}
