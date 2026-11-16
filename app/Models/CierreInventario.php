<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** Cierre diario de inventario registrado (foto del día de todos los insumos). */
class CierreInventario extends Model {
    protected $table = 'cierres_inventario';
    protected $fillable = ['fecha', 'id_usuario', 'insumos', 'con_movimiento', 'valor_entradas', 'valor_salidas', 'valor_final', 'bajo_minimo', 'observacion', 'detalle', 'segundos', 'valor_productos', 'detalle_productos'];
    protected $casts = ['fecha' => 'date', 'detalle' => 'array', 'detalle_productos' => 'array', 'valor_productos' => 'float', 'segundos' => 'integer', 'valor_entradas' => 'float', 'valor_salidas' => 'float', 'valor_final' => 'float'];

    public function usuario() { return $this->belongsTo(Usuario::class, 'id_usuario'); }

    /** Tiempo que tomó el cierre, legible: "45 s", "3 min 20 s". */
    public static function duracion(?int $segundos): ?string
    {
        if ($segundos === null) return null;
        if ($segundos < 60) return $segundos . ' s';
        $m = intdiv($segundos, 60); $s = $segundos % 60;
        return $m . ' min' . ($s ? ' ' . $s . ' s' : '');
    }

    public function getDuracionAttribute(): ?string { return self::duracion($this->segundos); }
}
