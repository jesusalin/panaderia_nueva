<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MateriaPrima extends Model {
    protected $table = 'materia_prima';
    protected $fillable = ['nombre','id_unidad','id_proveedor','stock_actual','stock_minimo','cantidad_reposicion','costo_unitario','estado','es_control'];
    // Cantidades como número (sin ceros de sobra al mostrarlas: 25.000 → 25)
    protected $casts = ['stock_actual' => 'float', 'stock_minimo' => 'float', 'cantidad_reposicion' => 'float', 'es_control' => 'boolean'];

    /** Días que se usan para calcular el consumo promedio. */
    public const DIAS_CONSUMO = 14;

    public function unidad()          { return $this->belongsTo(UnidadMedida::class, 'id_unidad'); }
    public function proveedor()       { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
    public function recetaDetalles()  { return $this->hasMany(RecetaDetalle::class, 'id_materia'); }
    public function compraDetalles()  { return $this->hasMany(CompraDetalle::class, 'id_materia'); }
    public function movimientos()     { return $this->hasMany(MovimientoInventario::class, 'id_materia'); }
    public function ordenesAutomaticas() { return $this->hasMany(OrdenAutomatica::class, 'id_materia'); }
    public function conteosFisicos()  { return $this->hasMany(ConteoFisico::class, 'id_materia'); }
    public function tieneStockBajo()  { return $this->stock_actual <= $this->stock_minimo; }
    public function estaAgotado()     { return $this->stock_actual <= 0; }

    /** Valor del stock en soles (stock × costo unitario). */
    public function getValorAttribute(): float
    {
        return round($this->stock_actual * (float) $this->costo_unitario, 2);
    }

    /**
     * Consumo promedio por día de varios insumos (lo que sale por producción y mermas
     * en los últimos DIAS_CONSUMO días). @return Collection id_materia => cantidad/día
     */
    public static function consumoDiario(iterable $ids): Collection
    {
        return MovimientoInventario::whereIn('id_materia', collect($ids))
            ->where('tipo', 'salida')->whereIn('motivo', ['produccion', 'merma'])
            ->where('created_at', '>=', now()->subDays(self::DIAS_CONSUMO))
            ->selectRaw('id_materia, SUM(cantidad) as total')->groupBy('id_materia')
            ->pluck('total', 'id_materia')->map(fn ($t) => round($t / self::DIAS_CONSUMO, 3));
    }

    /** Para cuántos días alcanza el stock con ese consumo (null si casi no se usa). */
    public function diasCobertura(?float $porDia): ?int
    {
        if (!$porDia || $porDia <= 0) return null;
        return (int) floor($this->stock_actual / $porDia);
    }
}
