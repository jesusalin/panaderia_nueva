<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Categoria extends Model {
    use HasFactory;

    protected $table = 'categorias';
    protected $fillable = ['nombre', 'descripcion', 'icono', 'color', 'estado'];

    /** Íconos que el admin puede elegir (clase Font Awesome => nombre). */
    public const ICONOS = [
        'fa-bread-slice'    => 'Pan',
        'fa-birthday-cake'  => 'Torta',
        'fa-cookie'         => 'Galleta',
        'fa-cookie-bite'    => 'Galleta mordida',
        'fa-cheese'         => 'Empanada / salado',
        'fa-pizza-slice'    => 'Pizza',
        'fa-hamburger'      => 'Sándwich',
        'fa-ice-cream'      => 'Postre helado',
        'fa-candy-cane'     => 'Dulces',
        'fa-egg'            => 'Huevo',
        'fa-mug-hot'        => 'Café / caliente',
        'fa-coffee'         => 'Taza',
        'fa-glass-whiskey'  => 'Bebida fría',
        'fa-wine-bottle'    => 'Botella',
        'fa-apple-alt'      => 'Fruta',
        'fa-seedling'       => 'Integral / natural',
        'fa-gift'           => 'Para regalo',
        'fa-star'           => 'Especial',
        'fa-box'            => 'Empaque',
        'fa-tag'            => 'Otro',
    ];

    /** Colores sugeridos (se puede elegir cualquier otro). */
    public const COLORES = ['#b5451b', '#d4880f', '#c0392b', '#8e44ad', '#2980b9', '#16a085', '#27ae60', '#6d4c41', '#34495e', '#e84393'];

    public const COLOR_DEFECTO = '#b5451b';

    public function productos() { return $this->hasMany(Producto::class, 'id_categoria'); }

    /** Ícono final: el elegido o, si no hay, uno adivinado por el nombre. */
    public function getIconoFinalAttribute(): string
    {
        if ($this->icono && array_key_exists($this->icono, self::ICONOS)) return $this->icono;

        $n = mb_strtolower((string) $this->nombre);
        return match (true) {
            str_contains($n, 'pastel') || str_contains($n, 'torta') || str_contains($n, 'queque') => 'fa-birthday-cake',
            str_contains($n, 'galleta')                              => 'fa-cookie',
            str_contains($n, 'empanada') || str_contains($n, 'salad') => 'fa-cheese',
            str_contains($n, 'bebida') || str_contains($n, 'café') || str_contains($n, 'cafe') => 'fa-mug-hot',
            str_contains($n, 'pan')                                  => 'fa-bread-slice',
            str_contains($n, 'postre') || str_contains($n, 'dulce')  => 'fa-candy-cane',
            default                                                  => 'fa-tag',
        };
    }

    /** Color final: el elegido o el de la marca. */
    public function getColorFinalAttribute(): string
    {
        return $this->color && preg_match('/^#[0-9a-fA-F]{6}$/', $this->color) ? $this->color : self::COLOR_DEFECTO;
    }
}
