<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model {
    use HasFactory;

    protected $table = 'productos';
    protected $fillable = ['id_categoria','nombre','descripcion','precio_venta','costo_produccion','stock_actual','stock_minimo','imagen','estado'];
    public function categoria()      { return $this->belongsTo(Categoria::class, 'id_categoria'); }
    public function receta()         { return $this->hasOne(Receta::class, 'id_producto'); }
    public function ventaDetalles()  { return $this->hasMany(VentaDetalle::class, 'id_producto'); }
    public function producciones()   { return $this->hasMany(Produccion::class, 'id_producto'); }
    public function kardex()         { return $this->hasMany(KardexProducto::class, 'id_producto'); }
    public function tieneStockBajo() { return $this->stock_actual <= $this->stock_minimo; }
    public function estaAgotado()    { return $this->stock_actual <= 0; }

    /** Ganancia por unidad (precio - costo); null si no tiene costo registrado. */
    public function getGananciaAttribute(): ?float
    {
        return $this->costo_produccion > 0 ? round($this->precio_venta - $this->costo_produccion, 2) : null;
    }

    /** Margen de ganancia sobre el precio de venta, en %. */
    public function getMargenAttribute(): ?float
    {
        return $this->costo_produccion > 0 && $this->precio_venta > 0
            ? round(($this->precio_venta - $this->costo_produccion) / $this->precio_venta * 100, 1)
            : null;
    }

    /**
     * Costo de una unidad según su receta: (Σ cantidad × costo del insumo) ÷ rendimiento.
     * null si no tiene receta o le faltan costos.
     */
    public function costoSegunReceta(): ?float
    {
        $receta = $this->relationLoaded('receta') ? $this->receta : $this->receta()->with('detalles.materia')->first();
        if (!$receta || $receta->rendimiento <= 0) return null;
        $receta->loadMissing('detalles.materia');
        if ($receta->detalles->isEmpty()) return null;

        $total = $receta->detalles->sum(fn ($d) => $d->cantidad * ($d->materia->costo_unitario ?? 0));
        return $total > 0 ? round($total / $receta->rendimiento, 2) : null;
    }
}
