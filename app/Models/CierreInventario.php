<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** Cierre diario de inventario registrado (foto del día de todos los insumos). */
class CierreInventario extends Model {
    protected $table = 'cierres_inventario';
    protected $fillable = ['fecha', 'id_usuario', 'insumos', 'con_movimiento', 'valor_entradas', 'valor_salidas', 'valor_final', 'bajo_minimo', 'observacion', 'detalle'];
    protected $casts = ['fecha' => 'date', 'detalle' => 'array', 'valor_entradas' => 'float', 'valor_salidas' => 'float', 'valor_final' => 'float'];

    public function usuario() { return $this->belongsTo(Usuario::class, 'id_usuario'); }
}
