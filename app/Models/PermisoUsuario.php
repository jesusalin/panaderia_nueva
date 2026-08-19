<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisoUsuario extends Model
{
    protected $table = 'permisos_usuario';
    public $timestamps = false;

    protected $fillable = ['id_usuario', 'modulo'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
