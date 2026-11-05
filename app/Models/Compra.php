<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model {
    protected $table = 'compras';
    protected $fillable = [
        'id_proveedor', 'id_usuario', 'numero_doc', 'fecha_compra', 'subtotal', 'igv', 'total', 'incluye_igv', 'estado', 'observaciones',
        'recibida_at', 'id_usuario_recibe', 'nota_recepcion', 'anulada_at', 'id_usuario_anula', 'motivo_anulacion',
    ];
    protected $casts = [
        'fecha_compra' => 'date', 'recibida_at' => 'datetime', 'anulada_at' => 'datetime', 'incluye_igv' => 'boolean',
        'subtotal' => 'float', 'igv' => 'float', 'total' => 'float',
    ];

    public const IGV = 0.18;
    /** Una compra pendiente con más de estos días se marca como atrasada */
    public const DIAS_ATRASO = 3;

    public function proveedor()     { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
    public function usuario()       { return $this->belongsTo(Usuario::class, 'id_usuario'); }
    public function usuarioRecibe() { return $this->belongsTo(Usuario::class, 'id_usuario_recibe'); }
    public function usuarioAnula()  { return $this->belongsTo(Usuario::class, 'id_usuario_anula'); }
    public function detalles()      { return $this->hasMany(CompraDetalle::class, 'id_compra'); }
    public function orden()         { return $this->hasOne(OrdenAutomatica::class, 'id_compra'); }

    /** "Compra #12" o el número de la factura si lo tiene */
    public function getCodigoAttribute(): string { return '#' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT); }

    /** Días que lleva esperando (solo pendientes) */
    public function getDiasEsperaAttribute(): ?int {
        if ($this->estado !== 'pendiente') return null;
        return (int) $this->fecha_compra->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);
    }
    public function getAtrasadaAttribute(): bool { return ($this->dias_espera ?? 0) > self::DIAS_ATRASO; }

    /** Llegó con alguna cantidad distinta a la pedida */
    public function getRecepcionIncompletaAttribute(): bool {
        return $this->estado === 'recibida' && $this->detalles->contains(fn ($d) => $d->cantidad_recibida !== null && abs($d->cantidad_recibida - $d->cantidad) > 0.0005);
    }

    /**
     * Calcula subtotal, IGV y total a partir de las líneas.
     * Si los precios incluyen IGV, el total es la suma y se separa el IGV de ahí.
     */
    public static function totales(float $suma, bool $incluyeIgv): array {
        $suma = round($suma, 2);
        if ($incluyeIgv) {
            $subtotal = round($suma / (1 + self::IGV), 2);
            return ['subtotal' => $subtotal, 'igv' => round($suma - $subtotal, 2), 'total' => $suma];
        }
        $igv = round($suma * self::IGV, 2);
        return ['subtotal' => $suma, 'igv' => $igv, 'total' => round($suma + $igv, 2)];
    }
}
