<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model {
    protected $table    = 'clientes';
    protected $fillable = [
        'nombre', 'tipo', 'ruc', 'dni', 'telefono', 
        'email', 'direccion', 'distrito', 'referencia', 'estado'
    ];

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
