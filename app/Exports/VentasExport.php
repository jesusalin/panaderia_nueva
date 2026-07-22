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
            'Tipo de Pago', 'Subtotal', 'IGV', 'Total', 'Estado',
        ];
    }

    public function map($venta): array
    {
        return [
            $venta->numero_venta,
            $venta->fecha_venta->format('d/m/Y H:i'),
            $venta->cliente->nombre ?? 'General',
            $venta->usuario->nombre ?? '—',
            ucfirst($venta->tipo_pago),
            round($venta->subtotal, 2),
            round($venta->igv, 2),
            round($venta->total, 2),
            ucfirst($venta->estado),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 18, 'C' => 26, 'D' => 20,
            'E' => 14, 'F' => 12, 'G' => 12, 'H' => 12, 'I' => 14,
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
