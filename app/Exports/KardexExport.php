<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta la ficha kardex (cronológica, con saldo) de un insumo o
 * producto puntual a Excel.
 */
class KardexExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(
        protected $item,
        protected Collection $movimientos,
        protected string $unidad,
    ) {}

    public function collection()
    {
        return $this->movimientos;
    }

    public function headings(): array
    {
        return [
            "Kardex: {$this->item->nombre}",
        ];
    }

    // Sobrescribimos encabezados reales en una segunda fila usando WithMapping + heading manual
    public function map($mov): array
    {
        return [
            $mov->created_at->format('d/m/Y H:i'),
            ucfirst($mov->tipo),
            ucfirst(str_replace('_', ' ', $mov->motivo)),
            $mov->tipo === 'entrada' ? round($mov->cantidad, 2) : '',
            $mov->tipo === 'salida'  ? round($mov->cantidad, 2) : '',
            round($mov->stock_despues, 2) . ' ' . $this->unidad,
            $mov->usuario->apodo ?? $mov->usuario->nombre ?? '—',
            $mov->observacion ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 12, 'C' => 16, 'D' => 10, 'E' => 10, 'F' => 14, 'G' => 18, 'H' => 34];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getDelegate()->insertNewRowBefore(1, 1);
        $sheet->setCellValue('A1', 'Fecha');
        $sheet->setCellValue('B1', 'Tipo');
        $sheet->setCellValue('C1', 'Motivo');
        $sheet->setCellValue('D1', 'Entrada');
        $sheet->setCellValue('E1', 'Salida');
        $sheet->setCellValue('F1', 'Saldo');
        $sheet->setCellValue('G1', 'Usuario');
        $sheet->setCellValue('H1', 'Observación');

        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                  'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A1A2E']]],
            2 => ['font' => ['bold' => true, 'italic' => true]],
        ];
    }

    public function title(): string
    {
        return 'Kardex';
    }
}
