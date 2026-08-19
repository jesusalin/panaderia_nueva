<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model {
    protected $table = 'compras';
    protected $fillable = ['id_proveedor','id_usuario','numero_doc','fecha_compra','subtotal','igv','total','estado','observaciones'];
    protected $casts = ['fecha_compra' => 'date'];
    public function proveedor() { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
    public function usuario()   { return $this->belongsTo(Usuario::class, 'id_usuario'); }
    public function detalles()  { return $this->hasMany(CompraDetalle::class, 'id_compra'); }
}
