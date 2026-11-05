<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model {
    protected $table = 'proveedores';
    protected $fillable = ['nombre', 'ruc', 'direccion', 'telefono', 'email', 'contacto', 'estado'];

    public function compras()            { return $this->hasMany(Compra::class, 'id_proveedor'); }
    public function ordenesAutomaticas() { return $this->hasMany(OrdenAutomatica::class, 'id_proveedor'); }
    public function insumos()            { return $this->hasMany(MateriaPrima::class, 'id_proveedor'); }

    /** Teléfono solo con números, listo para WhatsApp (Perú: 51 + 9 dígitos) */
    public function getWhatsappAttribute(): ?string {
        $n = preg_replace('/\D/', '', (string) $this->telefono);
        if (strlen($n) === 9 && $n[0] === '9') return '51' . $n;
        if (strlen($n) === 11 && str_starts_with($n, '519')) return $n;
        return null;
    }

    public function getIniciales(): string {
        $p = preg_split('/\s+/', trim(preg_replace('/\b(S\.?A\.?C?\.?|E\.?I\.?R\.?L\.?|S\.?R\.?L\.?)\b/i', '', $this->nombre)));
        return mb_strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1));
    }
}
