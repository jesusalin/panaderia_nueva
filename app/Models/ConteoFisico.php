<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConteoFisico extends Model
{
    protected $table = 'conteos_fisicos';

    protected $fillable = [
        'lote', 'id_materia', 'id_usuario',
        'stock_sistema', 'stock_fisico', 'diferencia', 'observacion',
    ];

    public function materia()
    {
        return $this->belongsTo(MateriaPrima::class, 'id_materia');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    /**
     * Exactitud de este ítem contado, en %. 100% = coincide exacto.
     * Si el conteo físico superó al del sistema, igual se penaliza
     * (un sobrante también es una imprecisión del inventario).
     */
    public function getExactitudAttribute(): float
    {
        if ($this->stock_sistema <= 0) {
            return $this->stock_fisico == 0 ? 100 : 0;
        }
        $exactitud = 100 - (abs($this->diferencia) / $this->stock_sistema * 100);
        return max(0, round($exactitud, 1));
    }
}
