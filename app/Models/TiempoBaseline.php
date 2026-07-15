<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiempoBaseline extends Model
{
    public $timestamps = false;
    protected $table = 'tiempos_baseline';

    protected $fillable = [
        'tipo_operacion',
        'segundos_manual',
        'updated_at',
    ];
}
