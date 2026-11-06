<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model {
    protected $table = 'ventas';
    protected $fillable = [
        'id_usuario', 'id_cliente', 'numero_venta', 'fecha_venta', 'subtotal', 'igv', 'total',
        'tipo_pago', 'monto_recibido', 'vuelto', 'numero_operacion',
        'estado', 'anulada_at', 'id_usuario_anula', 'motivo_anulacion', 'observaciones',
    ];
    protected $casts = [
        'fecha_venta' => 'datetime', 'anulada_at' => 'datetime',
        'subtotal' => 'float', 'igv' => 'float', 'total' => 'float', 'monto_recibido' => 'float', 'vuelto' => 'float',
    ];

    public const IGV = 0.18;

    /** Medios de pago: texto, ícono y color (un solo lugar para todas las vistas) */
    public const MEDIOS = [
        'efectivo' => ['texto' => 'Efectivo', 'icono' => 'fa-money-bill-wave', 'color' => '#1e8e5a'],
        'yape'     => ['texto' => 'Yape',     'icono' => 'fa-mobile-screen',   'color' => '#742284'],
        'plin'     => ['texto' => 'Plin',     'icono' => 'fa-mobile-screen',   'color' => '#0aa0c8'],
        'tarjeta'  => ['texto' => 'Tarjeta',  'icono' => 'fa-credit-card',     'color' => '#2c3e50'],
        'otro'     => ['texto' => 'Otro',     'icono' => 'fa-wallet',          'color' => '#6c757d'],
    ];

    public function usuario()      { return $this->belongsTo(Usuario::class, 'id_usuario'); }
    public function usuarioAnula() { return $this->belongsTo(Usuario::class, 'id_usuario_anula'); }
    public function cliente()      { return $this->belongsTo(Cliente::class, 'id_cliente'); }
    public function detalles()     { return $this->hasMany(VentaDetalle::class, 'id_venta'); }

    public function getMedioAttribute(): array { return self::MEDIOS[$this->tipo_pago] ?? self::MEDIOS['otro']; }

    /**
     * Los precios de venta ya incluyen el IGV (así se muestran al cliente):
     * del total se separa la base imponible y el IGV.
     */
    public static function totales(float $total): array {
        $total = round($total, 2);
        $subtotal = round($total / (1 + self::IGV), 2);
        return ['subtotal' => $subtotal, 'igv' => round($total - $subtotal, 2), 'total' => $total];
    }
}
