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
     * Costo promedio ponderado: mezcla lo que ya había (a su costo) con lo que entra (a su precio).
     * Si no había stock, el costo es el precio de lo que entra.
     */
    public static function costoPromedio(float $stockAntes, float $costoAntes, float $cantidad, float $precio): float
    {
        if ($stockAntes <= 0 || $costoAntes <= 0) return round($precio, 4);
        if ($cantidad <= 0) return round($costoAntes, 4);
        return round(($stockAntes * $costoAntes + $cantidad * $precio) / ($stockAntes + $cantidad), 4);
    }

    /**
     * Lo contrario: saca del promedio lo que había entrado (al anular una compra recibida).
     * Si no queda stock o el resultado no tiene sentido, se deja el costo como está.
     */
    public static function costoSinEntrada(float $stockAntes, float $costoAntes, float $cantidad, float $precio): float
    {
        $queda = $stockAntes - $cantidad;
        if ($queda <= 0.0005) return round($costoAntes, 4);
        $costo = ($stockAntes * $costoAntes - $cantidad * $precio) / $queda;
        return $costo > 0 ? round($costo, 4) : round($costoAntes, 4);
    }

    /**
     * Precios de cada proveedor por insumo, según las compras (no anuladas).
     * @return Collection id_materia => Collection de [id_proveedor, nombre, activo, precio (sin IGV), fecha, veces, promedio]
     *         ordenado del más barato al más caro.
     */
    public static function preciosPorProveedor(iterable $ids): Collection
    {
        $ids = collect($ids)->unique()->values();
        if ($ids->isEmpty()) return collect();

        return CompraDetalle::query()
            ->join('compras', 'compras.id', '=', 'compra_detalles.id_compra')
            ->join('proveedores', 'proveedores.id', '=', 'compras.id_proveedor')
            ->where('compras.estado', '!=', 'anulada')
            ->whereIn('compra_detalles.id_materia', $ids)
            ->orderByDesc('compras.fecha_compra')->orderByDesc('compras.id')
            ->get(['compra_detalles.id_materia', 'compra_detalles.precio_unitario', 'compras.incluye_igv', 'compras.fecha_compra',
                   'compras.id_proveedor', 'proveedores.nombre as proveedor', 'proveedores.estado as estado_proveedor'])
            ->groupBy('id_materia')
            ->map(fn ($filas) => $filas->groupBy('id_proveedor')->map(function ($f) {
                $neto = fn ($d) => $d->incluye_igv ? round($d->precio_unitario / (1 + Compra::IGV), 2) : (float) $d->precio_unitario;
                $ultimo = $f->first();
                return [
                    'id_proveedor' => (int) $ultimo->id_proveedor,
                    'nombre'       => $ultimo->proveedor,
                    'activo'       => $ultimo->estado_proveedor === 'activo',
                    'precio'       => $neto($ultimo),
                    'fecha'        => \Carbon\Carbon::parse($ultimo->fecha_compra),
                    'veces'        => $f->count(),
                    'promedio'     => round($f->avg(fn ($d) => $neto($d)), 2),
                ];
            })->sortBy('precio')->values());
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
