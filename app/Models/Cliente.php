<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model {
    use HasFactory;

    protected $table    = 'clientes';
    protected $fillable = [
        'nombre', 'tipo', 'contacto', 'ruc', 'dni', 'telefono',
        'email', 'direccion', 'distrito', 'referencia', 'notas', 'estado'
    ];

    /** Cliente de las ventas de mostrador (sin datos). No se borra, no se desactiva ni se renombra. */
    public const GENERAL = 'Cliente General';

    /** Días sin comprar a partir de los cuales un cliente que solía comprar se marca "por recuperar". */
    public const DIAS_SIN_COMPRAR = 30;

    /**
     * Catálogo visual por tipo de cliente: ícono, color de acento y etiqueta.
     * Centralizado aquí para no repetir el mismo array en cada vista (index,
     * create, edit, show) y para poder agregar tipos nuevos en un solo lugar.
     */
    public const TIPOS = [
        'bodega'       => ['icono' => 'fa-store',        'color' => '#b5451b', 'label' => 'Bodega'],
        'supermercado' => ['icono' => 'fa-cart-shopping', 'color' => '#2170a3', 'label' => 'Supermercado'],
        'colegio'      => ['icono' => 'fa-school',        'color' => '#8e44ad', 'label' => 'Colegio'],
        'restaurante'  => ['icono' => 'fa-utensils',      'color' => '#c0392b', 'label' => 'Restaurante'],
        'panaderia'    => ['icono' => 'fa-bread-slice',   'color' => '#b9770e', 'label' => 'Otra Panadería'],
        'particular'   => ['icono' => 'fa-user',          'color' => '#1e8e5a', 'label' => 'Particular'],
        'otro'         => ['icono' => 'fa-building',      'color' => '#6c757d', 'label' => 'Otro'],
    ];

    public function esGeneral(): bool {
        return $this->nombre === self::GENERAL;
    }

    public function scopeActivos($q) {
        return $q->where(fn ($w) => $w->where('estado', 'activo')->orWhereNull('estado'));
    }

    /** Número para wa.me si el teléfono es un celular peruano (9 dígitos que empiezan con 9). */
    public function getWhatsappAttribute(): ?string {
        $n = preg_replace('/\D/', '', (string) $this->telefono);
        if (strlen($n) === 9 && $n[0] === '9') return '51' . $n;
        if (strlen($n) === 11 && str_starts_with($n, '519')) return $n;
        return null;
    }

    public function getDocumentoAttribute(): ?string {
        return $this->ruc ? 'RUC ' . $this->ruc : ($this->dni ? 'DNI ' . $this->dni : null);
    }

    public function getIniciales(): string {
        $p = preg_split('/\s+/', trim(preg_replace('/\b(S\.?A\.?C?\.?|E\.?I\.?R\.?L\.?|S\.?R\.?L\.?|I\.?E\.?P?\.?)(?=\s|$)/i', '', $this->nombre)));
        return mb_strtoupper(mb_substr($p[0] ?? '?', 0, 1) . mb_substr($p[1] ?? '', 0, 1));
    }

    public function ventas() { 
        return $this->hasMany(Venta::class, 'id_cliente'); 
    }

    public function totalComprado() {
        return $this->ventas()->where('estado', 'completada')->sum('total');
    }

    public function getIconoAttribute(): string {
        return self::TIPOS[$this->tipo ?? 'particular']['icono'] ?? 'fa-user';
    }

    public function getColorTipoAttribute(): string {
        return self::TIPOS[$this->tipo ?? 'particular']['color'] ?? '#6c757d';
    }

    public function getTipoLabelAttribute(): string {
        return self::TIPOS[$this->tipo ?? 'particular']['label'] ?? ucfirst($this->tipo ?? 'Particular');
    }
}
