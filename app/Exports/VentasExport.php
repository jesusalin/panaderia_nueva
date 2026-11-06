<?php

namespace App\Exports;

use App\Models\Venta;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta el historial de ventas a Excel, para llevar evidencia de las
 * transacciones (anexos de tesis, cuadre de caja, etc.).
 */
class VentasExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(
        protected ?string $fechaDesde = null,
        protected ?string $fechaHasta = null,
    ) {}

    public function query()
    {
        $query = Venta::with(['usuario', 'cliente'])->orderBy('fecha_venta');

        if ($this->fechaDesde) $query->whereDate('fecha_venta', '>=', $this->fechaDesde);
        if ($this->fechaHasta) $query->whereDate('fecha_venta', '<=', $this->fechaHasta);

        return $query;
    }

    public function headings(): array
    {
        return [
            'N° Venta', 'Fecha', 'Cliente', 'Vendedor',
            'Pago', 'N° operación', 'Op. gravada', 'IGV (incluido)', 'Total', 'Recibido', 'Vuelto', 'Estado', 'Motivo de anulación',
        ];
    }

    public function map($venta): array
    {
        return [
            $venta->numero_venta,
            $venta->fecha_venta->format('d/m/Y H:i'),
            $venta->cliente->nombre ?? 'General',
            $venta->usuario->nombre ?? '—',
            $venta->medio['texto'],
            $venta->numero_operacion ?? '',
            round($venta->subtotal, 2),
            round($venta->igv, 2),
            round($venta->total, 2),
            $venta->monto_recibido !== null ? round($venta->monto_recibido, 2) : '',
            $venta->vuelto !== null ? round($venta->vuelto, 2) : '',
            ucfirst($venta->estado),
            $venta->motivo_anulacion ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 18, 'C' => 26, 'D' => 20,
            'E' => 12, 'F' => 14, 'G' => 13, 'H' => 14, 'I' => 12, 'J' => 11, 'K' => 10, 'L' => 13, 'M' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A1A2E']]],
        ];
    }
}
