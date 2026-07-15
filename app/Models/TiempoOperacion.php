<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiempoOperacion extends Model
{
    protected $table = 'tiempos_operacion';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tipo_operacion',
        'duracion_ms',
        'referencia_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    const TIPOS = [
        'busqueda_producto'  => 'Búsqueda de producto',
        'verificacion_stock' => 'Verificación de stock',
        'registro_venta'     => 'Registro de venta',
    ];
}
